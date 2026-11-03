<?php
declare(strict_types=1);

/**
 * A small LaTeX-maths to MathML converter (no JavaScript, no external library, works with the strict content policy).
 * Covers what physics teaching needs: fractions, roots, sub/superscripts, Greek, vectors/accents, integrals and sums with limits,
 * \left...\right, text and units, matrices and cases. Anything it does not understand is shown as the original LaTeX in a code box.
 */
final class Latex
{
    private const SYMBOLS = [
        'alpha' => 'α', 'beta' => 'β', 'gamma' => 'γ', 'delta' => 'δ', 'epsilon' => 'ε', 'varepsilon' => 'ε', 'zeta' => 'ζ', 'eta' => 'η',
        'theta' => 'θ', 'vartheta' => 'ϑ', 'iota' => 'ι', 'kappa' => 'κ', 'lambda' => 'λ', 'mu' => 'μ', 'nu' => 'ν', 'xi' => 'ξ', 'pi' => 'π',
        'varpi' => 'ϖ', 'rho' => 'ρ', 'varrho' => 'ϱ', 'sigma' => 'σ', 'varsigma' => 'ς', 'tau' => 'τ', 'upsilon' => 'υ', 'phi' => 'ϕ', 'varphi' => 'φ',
        'chi' => 'χ', 'psi' => 'ψ', 'omega' => 'ω', 'Gamma' => 'Γ', 'Delta' => 'Δ', 'Theta' => 'Θ', 'Lambda' => 'Λ', 'Xi' => 'Ξ', 'Pi' => 'Π',
        'Sigma' => 'Σ', 'Upsilon' => 'Υ', 'Phi' => 'Φ', 'Psi' => 'Ψ', 'Omega' => 'Ω',
        'hbar' => 'ℏ', 'ell' => 'ℓ', 'infty' => '∞', 'partial' => '∂', 'nabla' => '∇', 'degree' => '°', 'prime' => '′', 'aleph' => 'ℵ',
    ];
    private const OPS = [
        'cdot' => '⋅', 'times' => '×', 'pm' => '±', 'mp' => '∓', 'div' => '÷', 'leq' => '≤', 'le' => '≤', 'geq' => '≥', 'ge' => '≥', 'neq' => '≠', 'ne' => '≠',
        'approx' => '≈', 'sim' => '∼', 'simeq' => '≃', 'equiv' => '≡', 'propto' => '∝', 'to' => '→', 'rightarrow' => '→', 'leftarrow' => '←', 'Rightarrow' => '⇒',
        'Leftarrow' => '⇐', 'Leftrightarrow' => '⇔', 'leftrightarrow' => '↔', 'mapsto' => '↦', 'circ' => '∘', 'll' => '≪', 'gg' => '≫', 'in' => '∈', 'notin' => '∉',
        'subset' => '⊂', 'cup' => '∪', 'cap' => '∩', 'perp' => '⊥', 'parallel' => '∥', 'angle' => '∠', 'ldots' => '…', 'cdots' => '⋯', 'dots' => '…', 'vdots' => '⋮',
        'langle' => '⟨', 'rangle' => '⟩', 'forall' => '∀', 'exists' => '∃', 'oplus' => '⊕', 'otimes' => '⊗', 'star' => '⋆', 'bullet' => '•', 'because' => '∵', 'therefore' => '∴',
        'uparrow' => '↑', 'downarrow' => '↓', 'longrightarrow' => '⟶',
    ];
    private const BIG = ['int' => '∫', 'iint' => '∬', 'iiint' => '∭', 'oint' => '∮', 'sum' => '∑', 'prod' => '∏'];
    private const FUNCS = ['sin', 'cos', 'tan', 'cot', 'sec', 'csc', 'arcsin', 'arccos', 'arctan', 'sinh', 'cosh', 'tanh', 'log', 'ln', 'exp', 'lim', 'min', 'max', 'det', 'dim', 'ker', 'arg', 'sup', 'inf', 'mod', 'Pr'];
    private const ACCENTS = ['vec' => '→', 'hat' => '^', 'bar' => '¯', 'dot' => '˙', 'ddot' => '¨', 'tilde' => '˜', 'overrightarrow' => '→', 'widehat' => '^', 'check' => 'ˇ', 'acute' => '´', 'grave' => '`'];
    private const SPACES = [',' => '0.17em', ':' => '0.22em', ';' => '0.28em', '!' => '-0.17em', 'quad' => '1em', 'qquad' => '2em', ' ' => '0.28em'];

    private array $t = [];
    private int $i = 0;
    private bool $raw = false;

    /** Never throws: returns MathML, or the raw LaTeX in a code element when it cannot be parsed. */
    public static function render(string $tex, bool $display): string
    {
        $tex = trim($tex);
        if ($tex === '' || strlen($tex) > 4000) {
            return '<code class="math-raw">' . e($tex) . '</code>';
        }
        try {
            $p = new self();
            $p->t = self::tokenize($tex);
            $p->i = 0;
            $body = $p->sequence([]);
            if ($p->i < count($p->t)) {
                throw new RuntimeException('Unexpected token');
            }
            return '<math xmlns="http://www.w3.org/1998/Math/MathML" display="' . ($display ? 'block' : 'inline') . '"><semantics><mrow>' . $body
                . '</mrow><annotation encoding="application/x-tex">' . e($tex) . '</annotation></semantics></math>';
        } catch (Throwable) {
            return '<code class="math-raw" title="This formula could not be displayed">' . e($tex) . '</code>';
        }
    }

    /** @return string[] */
    private static function tokenize(string $s): array
    {
        $out = [];
        $n = strlen($s);
        for ($i = 0; $i < $n;) {
            $c = $s[$i];
            if ($c === '\\') {
                if ($i + 1 >= $n) {
                    throw new RuntimeException('Trailing backslash');
                }
                if (ctype_alpha($s[$i + 1])) {
                    $j = $i + 1;
                    while ($j < $n && ctype_alpha($s[$j])) {
                        $j++;
                    }
                    $out[] = '\\' . substr($s, $i + 1, $j - $i - 1);
                    $i = $j;
                } else {
                    $out[] = '\\' . $s[$i + 1];
                    $i += 2;
                }
            } elseif (ctype_space($c)) {
                $j = $i;
                while ($j < $n && ctype_space($s[$j])) {
                    $j++;
                }
                $out[] = ' '; // kept so 	ext{...} can show its spaces; skipped everywhere else
                $i = $j;
            } elseif (ctype_digit($c) || ($c === '.' && $i + 1 < $n && ctype_digit($s[$i + 1]))) {
                $j = $i;
                while ($j < $n && (ctype_digit($s[$j]) || ($s[$j] === '.' && $j + 1 < $n && ctype_digit($s[$j + 1])))) {
                    $j++;
                }
                $out[] = substr($s, $i, $j - $i);
                $i = $j;
            } else {
                $ch = mb_substr($s, 0, 0); // placate static analysis
                $len = ord($c) >= 0xF0 ? 4 : (ord($c) >= 0xE0 ? 3 : (ord($c) >= 0xC0 ? 2 : 1));
                $out[] = substr($s, $i, $len);
                $i += $len;
            }
        }
        return $out;
    }

    private function peek(): ?string
    {
        while (!$this->raw && ($this->t[$this->i] ?? null) === ' ') {
            $this->i++;
        }
        return $this->t[$this->i] ?? null;
    }

    private function next(): string
    {
        $this->peek(); // skips blanks
        if (!isset($this->t[$this->i])) {
            throw new RuntimeException('Unexpected end');
        }
        return $this->t[$this->i++];
    }

    /** Parses atoms until one of $stop tokens (not consumed) or the end. */
    private function sequence(array $stop, bool $upright = false): string
    {
        $out = '';
        while (($tok = $this->peek()) !== null && !in_array($tok, $stop, true)) {
            if ($tok === '}' || $tok === '\\right' || $tok === '\\end' || $tok === '&' || $tok === '\\\\') {
                break;
            }
            $out .= $this->scripted($upright);
        }
        return $out;
    }

    private function group(bool $upright = false): string
    {
        if ($this->peek() === '{') {
            $this->next();
            $inner = $this->sequence(['}'], $upright);
            if ($this->next() !== '}') {
                throw new RuntimeException('Missing }');
            }
            return '<mrow>' . $inner . '</mrow>';
        }
        return $this->atom($upright);
    }

    /** An atom followed by any ^ / _ scripts. */
    private function scripted(bool $upright): string
    {
        $tok = $this->peek();
        $big = $tok !== null && str_starts_with($tok, '\\') && (isset(self::BIG[substr($tok, 1)]) || $tok === '\\lim' || $tok === '\\max' || $tok === '\\min' || $tok === '\\sup' || $tok === '\\inf');
        $limitsOp = $tok !== null && in_array($tok, ['\\sum', '\\prod', '\\lim', '\\max', '\\min', '\\sup', '\\inf'], true);
        $base = $this->atom($upright);
        $sub = $sup = null;
        while (($p = $this->peek()) === '^' || $p === '_' || $p === "'") {
            $this->next();
            if ($p === "'") {
                $sup = ($sup ?? '') . '<mo>′</mo>';
                continue;
            }
            $arg = $this->group($upright);
            if ($p === '^') {
                $sup = $arg;
            } else {
                $sub = $arg;
            }
        }
        if ($sub === null && $sup === null) {
            return $base;
        }
        if ($limitsOp) {
            return $sub !== null && $sup !== null ? "<munderover>$base$sub$sup</munderover>" : ($sub !== null ? "<munder>$base$sub</munder>" : "<mover>$base$sup</mover>");
        }
        if ($sub !== null && $sup !== null) {
            return "<msubsup>$base$sub$sup</msubsup>";
        }
        return $sub !== null ? "<msub>$base$sub</msub>" : "<msup>$base<mrow>$sup</mrow></msup>";
    }

    private function atom(bool $upright): string
    {
        $tok = $this->next();
        if ($tok === '{') {
            $this->i--;
            return $this->group($upright);
        }
        if ($tok === '^' || $tok === '_') {
            return '<mrow></mrow>'; // script with empty base
        }
        if (is_numeric($tok[0]) || ($tok[0] === '.' && strlen($tok) > 1)) {
            return '<mn>' . e($tok) . '</mn>';
        }
        if ($tok[0] !== '\\') {
            return $this->plain($tok, $upright);
        }
        $name = substr($tok, 1);

        if (isset(self::SYMBOLS[$name])) {
            return '<mi' . ($upright ? ' mathvariant="normal"' : '') . '>' . self::SYMBOLS[$name] . '</mi>';
        }
        if (isset(self::OPS[$name])) {
            return '<mo>' . self::OPS[$name] . '</mo>';
        }
        if (isset(self::BIG[$name])) {
            return '<mo largeop="true" movablelimits="false" stretchy="false">' . self::BIG[$name] . '</mo>';
        }
        if (in_array($name, self::FUNCS, true)) {
            return '<mrow><mi mathvariant="normal">' . $name . '</mi><mo>&#x2061;</mo></mrow>';
        }
        if (isset(self::ACCENTS[$name])) {
            $arg = $this->group($upright);
            return '<mover accent="true">' . $arg . '<mo>' . self::ACCENTS[$name] . '</mo></mover>';
        }
        if (array_key_exists($name, self::SPACES)) {
            return '<mspace width="' . self::SPACES[$name] . '"/>';
        }
        switch ($name) {
            case 'frac':
            case 'dfrac':
            case 'tfrac':
                return '<mfrac>' . $this->group($upright) . $this->group($upright) . '</mfrac>';
            case 'binom':
                return '<mrow><mo>(</mo><mfrac linethickness="0">' . $this->group($upright) . $this->group($upright) . '</mfrac><mo>)</mo></mrow>';
            case 'sqrt':
                if ($this->peek() === '[') {
                    $this->next();
                    $deg = $this->sequence([']']);
                    $this->next();
                    return '<mroot>' . $this->group($upright) . '<mrow>' . $deg . '</mrow></mroot>';
                }
                return '<msqrt>' . $this->group($upright) . '</msqrt>';
            case 'overline':
                return '<mover accent="true">' . $this->group($upright) . '<mo>¯</mo></mover>';
            case 'underline':
                return '<munder accent="true">' . $this->group($upright) . '<mo>_</mo></munder>';
            case 'text':
            case 'textrm':
            case 'mbox':
            case 'textnormal':
                return '<mtext>' . e($this->rawGroup(true)) . '</mtext>';
            case 'mathrm':
            case 'operatorname':
            case 'rm':
                return $this->group(true);
            case 'mathbf':
            case 'boldsymbol':
            case 'bm':
            case 'textbf':
                return '<mstyle mathvariant="bold">' . $this->group($upright) . '</mstyle>';
            case 'mathit':
            case 'textit':
                return '<mstyle mathvariant="italic">' . $this->group(false) . '</mstyle>';
            case 'mathbb':
                return '<mstyle mathvariant="double-struck">' . $this->group(true) . '</mstyle>';
            case 'mathcal':
            case 'mathscr':
                return '<mstyle mathvariant="script">' . $this->group(true) . '</mstyle>';
            case 'displaystyle':
            case 'textstyle':
            case 'scriptstyle':
            case 'limits':
            case 'nolimits':
                return '';
            case 'left':
                return $this->leftRight();
            case 'begin':
                return $this->environment();
            case '\\':
            case 'right':
            case 'end':
                throw new RuntimeException('Unexpected ' . $tok);
            case '{':
            case '}':
            case '%':
            case '$':
            case '&':
            case '_':
            case '#':
                return '<mo>' . e($name) . '</mo>';
            case '|':
                return '<mo>‖</mo>';
            case 'lbrace':
                return '<mo>{</mo>';
            case 'rbrace':
                return '<mo>}</mo>';
        }
        throw new RuntimeException('Unknown command ' . $tok);
    }

    private function plain(string $ch, bool $upright): string
    {
        if (ctype_alpha($ch) || (strlen($ch) > 1)) {
            return '<mi' . ($upright ? ' mathvariant="normal"' : '') . '>' . e($ch) . '</mi>';
        }
        $map = ['-' => '−', '*' => '∗', '/' => '/', '|' => '|'];
        if (in_array($ch, ['(', ')', '[', ']'], true)) {
            return '<mo stretchy="false">' . $ch . '</mo>';
        }
        if (in_array($ch, ['+', '-', '=', '<', '>', '*', '/', ',', ';', ':', '!', '|', '?', '.', '~'], true)) {
            return '<mo>' . e($map[$ch] ?? $ch) . '</mo>';
        }
        throw new RuntimeException('Unsupported character');
    }

    /** Text inside {...} read literally (for \text and environment names), spaces included. */
    private function rawGroup(bool $keepEdges = false): string
    {
        if ($this->next() !== '{') {
            throw new RuntimeException('Expected {');
        }
        $this->raw = true;
        try {
            $depth = 1;
            $out = '';
            while ($depth > 0) {
                $tok = $this->next();
                if ($tok === '{') {
                    $depth++;
                } elseif ($tok === '}') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
                if ($tok[0] === '\\') {
                    $out .= strlen($tok) === 2 ? $tok[1] : (self::SYMBOLS[substr($tok, 1)] ?? self::OPS[substr($tok, 1)] ?? '');
                } else {
                    $out .= $tok;
                }
            }
        } finally {
            $this->raw = false;
        }
        $out = preg_replace('/\s+/', ' ', $out);
        return $keepEdges ? preg_replace(['/^ /', '/ $/'], " ", $out) : trim($out);
    }
    private function delim(): string
    {
        $d = $this->next();
        return match ($d) {
            '.' => '',
            '\\{', '\\lbrace' => '{',
            '\\}', '\\rbrace' => '}',
            '\\langle' => '⟨',
            '\\rangle' => '⟩',
            '\\|' => '‖',
            '\\lvert', '\\rvert' => '|',
            default => in_array($d, ['(', ')', '[', ']', '|', '/'], true) ? $d : throw new RuntimeException('Bad delimiter'),
        };
    }

    private function leftRight(): string
    {
        $open = $this->delim();
        $inner = $this->sequence(['\\right']);
        if ($this->next() !== '\\right') {
            throw new RuntimeException('Missing \\right');
        }
        $close = $this->delim();
        return '<mrow>' . ($open !== '' ? '<mo fence="true" stretchy="true">' . e($open) . '</mo>' : '') . $inner . ($close !== '' ? '<mo fence="true" stretchy="true">' . e($close) . '</mo>' : '') . '</mrow>';
    }

    private function environment(): string
    {
        $name = $this->rawGroup();
        $rows = [[]];
        $cell = '';
        while (true) {
            $cell = $this->sequence([]);
            $tok = $this->peek();
            if ($tok === '&') {
                $this->next();
                $rows[count($rows) - 1][] = $cell;
            } elseif ($tok === '\\\\') {
                $this->next();
                $rows[count($rows) - 1][] = $cell;
                $rows[] = [];
            } elseif ($tok === '\\end') {
                $this->next();
                $rows[count($rows) - 1][] = $cell;
                if ($this->rawGroup() !== $name) {
                    throw new RuntimeException('Mismatched environment');
                }
                break;
            } else {
                throw new RuntimeException('Bad environment');
            }
        }
        $rows = array_values(array_filter($rows, fn($r) => !(count($r) === 1 && $r[0] === '')));
        $table = '<mtable' . ($name === 'cases' ? ' columnalign="left left"' : '') . '>';
        foreach ($rows as $r) {
            $table .= '<mtr>' . implode('', array_map(fn($c) => '<mtd><mrow>' . $c . '</mrow></mtd>', $r)) . '</mtr>';
        }
        $table .= '</mtable>';
        return match ($name) {
            'pmatrix' => '<mrow><mo>(</mo>' . $table . '<mo>)</mo></mrow>',
            'bmatrix' => '<mrow><mo>[</mo>' . $table . '<mo>]</mo></mrow>',
            'vmatrix' => '<mrow><mo>|</mo>' . $table . '<mo>|</mo></mrow>',
            'cases' => '<mrow><mo>{</mo>' . $table . '</mrow>',
            'matrix', 'aligned', 'align', 'array', 'gather', 'split' => $table,
            default => throw new RuntimeException('Unsupported environment'),
        };
    }
}
