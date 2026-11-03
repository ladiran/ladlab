<?php
declare(strict_types=1);

/**
 * Phase 4 seed: a sample University Physics I: Mechanics course (8 modules, 30 lessons, quizzes and a final exam) written in
 * Markdown + LaTeX, plus four "coming soon" University Physics courses, and switches Learn on. The Mechanics course is marked
 * as SAMPLE content: it disappears from the website as soon as you publish a real course of your own.
 */
return function (PDO $pdo): void {
    if ((int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn() > 0) {
        return;
    }
    $note = "*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n";

    $modules = [
        ['Units, Measurement and Vectors', 'The language of physics: units, uncertainty and vectors.', [
            ['Physical quantities and SI units', 1, 9, 'Name the SI base units; use dimensional analysis to check an equation.', <<<'T'
Every physical quantity has a **magnitude** and a **unit**. The SI system is built on seven base units; in mechanics we mostly use the metre (m), kilogram (kg) and second (s).

Other units are *derived*. For example the newton is $1\,\mathrm{N} = 1\,\mathrm{kg\,m/s^2}$.

## Dimensional analysis

Both sides of a correct equation must have the same dimensions. Writing $[L]$, $[M]$, $[T]$ for length, mass and time:

$$[F] = [M][L][T]^{-2}$$

**Check:** is $x = v t + \tfrac12 a t^2$ dimensionally consistent? $[v t] = [L][T]^{-1}[T] = [L]$ and $[a t^2] = [L][T]^{-2}[T]^2 = [L]$. Yes.
T],
            ['Significant figures and uncertainty', 0, 8, 'Report a result with the correct number of significant figures and an uncertainty.', <<<'T'
A measurement is never exact. We write $x = 12.3 \pm 0.2\,\mathrm{cm}$.

- Multiplying or dividing: keep as many significant figures as the *least* precise factor.
- Adding or subtracting: keep as many decimal places as the *least* precise term.

The **percentage uncertainty** is $\dfrac{\delta x}{x}\times 100\%$. When quantities are multiplied, their percentage uncertainties add (for a rough estimate).
T],
            ['Vectors: components and magnitude', 0, 12, 'Resolve a vector into components and find its magnitude and direction.', <<<'T'
A **vector** has magnitude and direction. In two dimensions we write

$$\vec{A} = A_x\,\hat{x} + A_y\,\hat{y}$$

with components $A_x = A\cos\theta$ and $A_y = A\sin\theta$. The magnitude and direction are

$$A = \sqrt{A_x^2 + A_y^2}, \qquad \theta = \tan^{-1}\!\left(\frac{A_y}{A_x}\right)$$

**Example.** $\vec{A} = 3\hat{x} + 4\hat{y}$ has magnitude $\sqrt{3^2+4^2} = 5$.
T],
            ['Scalar and vector products', 0, 11, 'Compute dot and cross products and interpret them physically.', <<<'T'
The **dot (scalar) product** gives a number:

$$\vec{A}\cdot\vec{B} = AB\cos\theta = A_xB_x + A_yB_y + A_zB_z$$

Work is a dot product: $W = \vec{F}\cdot\vec{d}$. Perpendicular vectors have a zero dot product.

The **cross (vector) product** gives a vector of magnitude $|\vec{A}\times\vec{B}| = AB\sin\theta$, directed by the right-hand rule. Torque is a cross product: $\vec{\tau} = \vec{r}\times\vec{F}$.
T],
        ]],
        ['Motion in One and Two Dimensions', 'Describing motion with kinematics.', [
            ['Position, velocity and acceleration', 1, 10, 'Define and relate displacement, velocity and acceleration using derivatives.', <<<'T'
Velocity and acceleration are the rates of change of position and velocity:

$$v = \frac{dx}{dt}, \qquad a = \frac{dv}{dt} = \frac{d^2x}{dt^2}$$

Going the other way we integrate: $\Delta x = \int_{t_1}^{t_2} v\,dt$. The area under a velocity–time graph is the displacement.
T],
            ['Motion with constant acceleration', 0, 14, 'Apply the four constant-acceleration equations.', <<<'T'
For constant $a$:

$$v = v_0 + a t$$
$$x = x_0 + v_0 t + \tfrac12 a t^2$$
$$v^2 = v_0^2 + 2a\,(x - x_0)$$

**Example.** A car starts from rest and accelerates at $2\,\mathrm{m/s^2}$ for $5\,\mathrm{s}$. Then $x = \tfrac12 (2)(5)^2 = 25\,\mathrm{m}$ and $v = 10\,\mathrm{m/s}$.
T],
            ['Projectile motion', 0, 15, 'Analyse horizontal and vertical motion independently.', <<<'T'
Ignoring air resistance, a projectile has **no horizontal acceleration** and a vertical acceleration of $-g$.

With launch speed $v_0$ at angle $\theta$:

$$x = (v_0\cos\theta)\,t, \qquad y = (v_0\sin\theta)\,t - \tfrac12 g t^2$$

The range on level ground is

$$R = \frac{v_0^2 \sin 2\theta}{g}$$

which is largest at $\theta = 45^\circ$.
T],
            ['Relative and circular motion', 0, 10, 'Use relative velocity and the centripetal acceleration.', <<<'T'
**Relative velocity:** $\vec{v}_{A/C} = \vec{v}_{A/B} + \vec{v}_{B/C}$.

An object moving in a circle of radius $r$ at constant speed $v$ accelerates towards the centre:

$$a_c = \frac{v^2}{r} = \omega^2 r, \qquad T = \frac{2\pi r}{v}$$
T],
        ]],
        ['Newton\'s Laws of Motion', 'Force and motion.', [
            ['Newton\'s first and second laws', 1, 12, 'State the laws and apply $\vec F_{net} = m\vec a$.', <<<'T'
**First law:** a body keeps its velocity unless a net external force acts on it.

**Second law:**

$$\sum \vec{F} = m\vec{a}$$

**Example.** A $5\,\mathrm{kg}$ block pushed by a net force of $20\,\mathrm{N}$ accelerates at $a = F/m = 4\,\mathrm{m/s^2}$.
T],
            ['Free-body diagrams and the third law', 0, 11, 'Draw free-body diagrams and identify action–reaction pairs.', <<<'T'
Draw the object alone, then draw **every force acting on it**: weight $m\vec g$, normal force $\vec N$, tension $\vec T$, friction $\vec f$.

**Third law:** if A pushes on B with force $\vec F$, then B pushes on A with $-\vec F$. The two forces act on *different* objects, so they never cancel in a free-body diagram.
T],
            ['Friction and drag', 0, 10, 'Use the friction model and interpret terminal velocity.', <<<'T'
Static friction satisfies $f_s \le \mu_s N$; kinetic friction is $f_k = \mu_k N$.

**Example.** A crate with $N = 50\,\mathrm{N}$ and $\mu_k = 0.3$ experiences $f_k = 15\,\mathrm{N}$.

At high speed, drag grows with speed until it balances weight: the object reaches **terminal velocity**.
T],
            ['Applications: inclines and pulleys', 0, 16, 'Solve inclined-plane and Atwood-machine problems.', <<<'T'
On a frictionless incline of angle $\theta$ the acceleration down the slope is

$$a = g\sin\theta$$

For an Atwood machine with masses $m_1 > m_2$:

$$a = \frac{m_1 - m_2}{m_1 + m_2}\,g, \qquad T = \frac{2 m_1 m_2}{m_1 + m_2}\,g$$
T],
        ]],
        ['Work and Energy', 'Energy methods for solving problems.', [
            ['Work done by a force', 1, 12, 'Calculate the work done by constant and variable forces.', <<<'T'
For a constant force, $W = \vec{F}\cdot\vec{d} = Fd\cos\theta$. For a variable force along a path,

$$W = \int_{\vec r_1}^{\vec r_2} \vec{F}\cdot d\vec{r}$$

The work done by a spring that is stretched from $0$ to $x$ is $W = \tfrac12 k x^2$.
T],
            ['Kinetic energy and the work–energy theorem', 0, 10, 'Use $W_{net} = \Delta K$.', <<<'T'
$$K = \tfrac12 m v^2, \qquad W_{net} = \Delta K = K_f - K_i$$

**Example.** A $2\,\mathrm{kg}$ ball moving at $3\,\mathrm{m/s}$ has $K = \tfrac12(2)(3)^2 = 9\,\mathrm{J}$.
T],
            ['Potential energy', 0, 11, 'Define gravitational and elastic potential energy.', <<<'T'
A conservative force has a potential energy $U$ with

$$\Delta U = -\int \vec{F}\cdot d\vec{r}, \qquad F_x = -\frac{dU}{dx}$$

Near the Earth's surface $U = mgh$; for a spring $U = \tfrac12 k x^2$.
T],
            ['Conservation of energy and power', 0, 13, 'Apply $E = K + U$ conservation and compute power.', <<<'T'
If only conservative forces do work, the **mechanical energy** is constant:

$$K_i + U_i = K_f + U_f$$

**Power** is the rate of doing work:

$$P = \frac{dW}{dt} = \vec{F}\cdot\vec{v}$$

The SI unit is the watt, $1\,\mathrm{W} = 1\,\mathrm{J/s}$.
T],
        ]],
        ['Momentum and Collisions', 'Impulse, conservation of momentum and collisions.', [
            ['Momentum and impulse', 1, 9, 'Relate impulse to the change in momentum.', <<<'T'
$$\vec{p} = m\vec{v}, \qquad \vec{F}_{net} = \frac{d\vec{p}}{dt}$$

**Impulse:** $\vec{J} = \int \vec{F}\,dt = \Delta\vec{p}$. A long contact time lowers the average force for the same impulse: this is why airbags work.
T],
            ['Conservation of momentum', 0, 10, 'Use conservation of momentum in isolated systems.', <<<'T'
If the net external force on a system is zero, its total momentum is constant:

$$\sum \vec{p}_{initial} = \sum \vec{p}_{final}$$
T],
            ['Elastic and inelastic collisions', 0, 14, 'Distinguish collision types and solve one-dimensional collisions.', <<<'T'
In every isolated collision momentum is conserved. In an **elastic** collision kinetic energy is conserved too. For a 1-D elastic collision:

$$v_{1f} = \frac{m_1 - m_2}{m_1 + m_2}\,v_{1i}, \qquad v_{2f} = \frac{2m_1}{m_1 + m_2}\,v_{1i}\quad (m_2\ \text{initially at rest})$$

In a **perfectly inelastic** collision the objects stick together: $v_f = \dfrac{m_1 v_1 + m_2 v_2}{m_1 + m_2}$.
T],
            ['Centre of mass', 0, 9, 'Locate the centre of mass of a system.', <<<'T'
$$\vec{R}_{cm} = \frac{\sum m_i \vec{r}_i}{\sum m_i}$$

The centre of mass of a system moves as if all the mass were concentrated there and all external forces acted on it: $M\vec{a}_{cm} = \sum \vec{F}_{ext}$.
T],
        ]],
        ['Rotation of Rigid Bodies', 'Rotational kinematics and dynamics.', [
            ['Angular kinematics', 1, 9, 'Relate angular and linear quantities.', <<<'T'
The rotational equations mirror the linear ones:

$$\omega = \omega_0 + \alpha t, \qquad \theta = \theta_0 + \omega_0 t + \tfrac12\alpha t^2$$

and for a point at radius $r$: $v = \omega r$, $a_t = \alpha r$.
T],
            ['Torque and moment of inertia', 0, 12, 'Compute torque and moment of inertia.', <<<'T'
$$\tau = rF\sin\theta, \qquad I = \sum m_i r_i^2$$

For a solid disc about its axis $I = \tfrac12 M R^2$; for a thin hoop $I = MR^2$.
T],
            ['Rotational dynamics', 0, 11, 'Use $\tau_{net} = I\alpha$.', <<<'T'
$$\sum \tau = I\alpha, \qquad K_{rot} = \tfrac12 I\omega^2$$

The **parallel-axis theorem**: $I = I_{cm} + M d^2$.
T],
            ['Angular momentum and rolling', 0, 13, 'Apply conservation of angular momentum; analyse rolling.', <<<'T'
$$\vec{L} = \vec{r}\times\vec{p} = I\vec{\omega}, \qquad \vec{\tau}_{net} = \frac{d\vec{L}}{dt}$$

Rolling without slipping: $v_{cm} = \omega R$ and the total kinetic energy is $\tfrac12 M v_{cm}^2 + \tfrac12 I_{cm}\omega^2$.
T],
        ]],
        ['Gravitation', 'Newton\'s law of gravitation and orbits.', [
            ['Newton\'s law of gravitation', 1, 10, 'State and use the inverse-square law.', <<<'T'
$$F = G\frac{m_1 m_2}{r^2}$$

with $G = 6.674\times 10^{-11}\,\mathrm{N\,m^2/kg^2}$. At the Earth's surface $g = GM/R^2 \approx 9.8\,\mathrm{m/s^2}$.
T],
            ['Gravitational potential energy and escape speed', 0, 11, 'Compute $U(r)$ and the escape speed.', <<<'T'
$$U(r) = -\frac{G M m}{r}, \qquad v_{esc} = \sqrt{\frac{2GM}{R}}$$

For the Earth, $v_{esc} \approx 11.2\,\mathrm{km/s}$.
T],
            ['Orbits and Kepler\'s laws', 0, 14, 'Use Kepler\'s third law for circular and elliptical orbits.', <<<'T'
For a circular orbit, setting gravity equal to the centripetal force gives

$$v = \sqrt{\frac{GM}{r}}, \qquad T^2 = \frac{4\pi^2}{GM}\,r^3$$

**Kepler's laws:** (1) orbits are ellipses with the Sun at a focus; (2) equal areas are swept in equal times; (3) $T^2\propto a^3$.
T],
        ]],
        ['Oscillations', 'Simple harmonic motion and beyond.', [
            ['Simple harmonic motion', 1, 13, 'Write and interpret $x(t) = A\cos(\omega t + \phi)$.', <<<'T'
A restoring force $F = -kx$ produces simple harmonic motion:

$$x(t) = A\cos(\omega t + \phi), \qquad \omega = \sqrt{\frac{k}{m}}, \qquad T = \frac{2\pi}{\omega}$$

**Example.** $k = 100\,\mathrm{N/m}$ and $m = 1\,\mathrm{kg}$ give $\omega = 10\,\mathrm{rad/s}$ and $T \approx 0.63\,\mathrm{s}$.
T],
            ['Energy in SHM and the pendulum', 0, 12, 'Use energy conservation in SHM; find the pendulum period.', <<<'T'
The total energy is constant: $E = \tfrac12 k A^2 = \tfrac12 m v^2 + \tfrac12 k x^2$.

For a simple pendulum with small angles,

$$T = 2\pi\sqrt{\frac{L}{g}}$$
T],
            ['Damped and driven oscillations', 0, 11, 'Describe damping and resonance qualitatively.', <<<'T'
With a damping force $-b v$, the amplitude decays like $e^{-bt/2m}$. A periodic driving force produces **resonance** when its frequency is close to the natural frequency $\omega_0 = \sqrt{k/m}$.
T],
        ]],
    ];

    $pdo->prepare("INSERT INTO courses (slug, title, subtitle, subject, level, description, outcomes, requirements, price, status, is_featured, sort_order, pass_mark, certificate_enabled, is_sample)
        VALUES ('university-physics-i-mechanics', 'University Physics I: Mechanics', 'From vectors to oscillations: first-year mechanics taught clearly.', 'Physics', 'University Year 1', ?, ?, ?, 15000, 'published', 1, 10, 60, 1, 1)")
        ->execute([
            "**Sample course description.** Replace this with your own. This course covers the full first-year mechanics syllabus: units and vectors, kinematics, Newton's laws, energy, momentum, rotation, gravitation and oscillations. Every lesson has notes with worked equations, and every module ends with a quiz.\n\nA free preview lesson in each module lets you try the course before you enrol.",
            "- Resolve vectors and use dot and cross products\n- Solve kinematics and projectile problems\n- Apply Newton's laws, including friction and inclines\n- Use work, energy and power\n- Analyse collisions with momentum conservation\n- Describe rotation, gravitation and simple harmonic motion",
            "- A–level / WAEC Physics and Mathematics\n- Basic calculus (derivatives and integrals) is helpful",
        ]);
    $cid = (int)$pdo->lastInsertId();

    $insM = $pdo->prepare('INSERT INTO course_modules (course_id, title, summary, sort_order) VALUES (?, ?, ?, ?)');
    $insL = $pdo->prepare("INSERT INTO lessons (course_id, module_id, title, sort_order, objectives, content, video_minutes, is_free_preview, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')");
    $modIds = [];
    foreach ($modules as $mi => [$mTitle, $mSummary, $lessons]) {
        $insM->execute([$cid, $mTitle, $mSummary, ($mi + 1) * 10]);
        $mid = (int)$pdo->lastInsertId();
        $modIds[$mi + 1] = $mid;
        foreach ($lessons as $li => [$lTitle, $free, $mins, $obj, $body]) {
            $insL->execute([$cid, $mid, $lTitle, ($li + 1) * 10, $obj, $note . $body, $mins, $free]);
        }
    }

    // ---- quizzes
    $insQ = $pdo->prepare('INSERT INTO quizzes (course_id, module_id, title, kind, instructions, pass_mark, time_limit_minutes, max_attempts, questions_to_draw, shuffle, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, \'published\', ?)');
    $insQn = $pdo->prepare('INSERT INTO questions (quiz_id, type, prompt, explanation, points, num_answer, num_tolerance, num_unit, accepted, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insO = $pdo->prepare('INSERT INTO question_options (question_id, label, is_correct, sort_order) VALUES (?, ?, ?, ?)');
    $addQuiz = function (?int $moduleId, string $title, string $kind, int $pass, int $minutes, int $attempts, int $draw, int $order, array $questions) use ($pdo, $cid, $insQ, $insQn, $insO): void {
        $insQ->execute([$cid, $moduleId, $title, $kind, $kind === 'final' ? 'Take your time. Calculators are allowed. You need the pass mark to earn your certificate.' : 'Check your understanding. You can retake this quiz as often as you like.', $pass, $minutes, $attempts, $draw, $order]);
        $qid = (int)$pdo->lastInsertId();
        foreach ($questions as $k => $q) {
            [$type, $prompt, $expl, $pts, $num, $tol, $unit, $accepted, $opts] = $q + [null, null, null, 1, null, 1.0, '', null, []];
            $insQn->execute([$qid, $type, $prompt, $expl, $pts, $num, $tol, $unit, $accepted, ($k + 1) * 10]);
            $quid = (int)$pdo->lastInsertId();
            foreach ($opts as $oi => [$label, $correct]) {
                $insO->execute([$quid, $label, $correct ? 1 : 0, ($oi + 1) * 10]);
            }
        }
    };

    $addQuiz($modIds[1], 'Quiz: Units and vectors', 'quiz', 60, 0, 0, 0, 15, [
        ['mcq', 'Which is the SI unit of force?', 'The newton: $1\,\mathrm{N} = 1\,\mathrm{kg\,m/s^2}$.', 1, null, 1.0, '', null, [['newton (N)', 1], ['joule (J)', 0], ['watt (W)', 0], ['pascal (Pa)', 0]]],
        ['truefalse', 'The dot product of two perpendicular vectors is zero.', '$\vec A\cdot\vec B = AB\cos 90^\circ = 0$.', 1, null, 1.0, '', null, [['True', 1], ['False', 0]]],
        ['numeric', 'What is the magnitude of the vector $\vec A = 3\hat{x} + 4\hat{y}$?', '$\sqrt{3^2+4^2}=5$.', 1, 5.0, 0.5, '', null, []],
        ['numeric', 'Find $\vec A\cdot\vec B$ for $\vec A = (2, 3, 1)$ and $\vec B = (1, -1, 4)$.', '$2(1)+3(-1)+1(4)=3$.', 2, 3.0, 0.0, '', null, []],
        ['short', 'What rule gives the direction of a cross product? (two or three words)', 'The right-hand rule.', 1, null, 1.0, '', "right-hand rule\nright hand rule\nthe right-hand rule\nthe right hand rule", []],
        ['multi', 'Select ALL the vector quantities.', 'Displacement, velocity and force have direction; speed does not.', 2, null, 1.0, '', null, [['displacement', 1], ['speed', 0], ['velocity', 1], ['force', 1], ['mass', 0]]],
    ]);
    $addQuiz($modIds[2], 'Quiz: Kinematics', 'quiz', 60, 0, 0, 0, 25, [
        ['numeric', 'A car starts from rest and accelerates at $2\,\mathrm{m/s^2}$ for $5\,\mathrm{s}$. How far does it travel? (metres)', '$x = \tfrac12 a t^2 = 25\,\mathrm{m}$.', 1, 25.0, 1.0, 'm', null, []],
        ['numeric', 'A ball is launched at $20\,\mathrm{m/s}$ at $30^\circ$ above the horizontal on level ground ($g = 9.8\,\mathrm{m/s^2}$). Find its range in metres.', '$R = v_0^2\sin 2\theta / g = 400(0.866)/9.8 \approx 35.3\,\mathrm{m}$.', 2, 35.35, 1.0, 'm', null, []],
        ['mcq', 'Ignoring air resistance, what is the horizontal acceleration of a projectile?', 'No horizontal force acts, so $a_x = 0$.', 1, null, 1.0, '', null, [['zero', 1], ['$g$', 0], ['$-g$', 0], ['$v_0\cos\theta$', 0]]],
    ]);
    $addQuiz($modIds[3], 'Quiz: Newton\'s laws', 'quiz', 60, 0, 0, 0, 35, [
        ['numeric', 'A net force of $20\,\mathrm{N}$ acts on a $5\,\mathrm{kg}$ block. What is its acceleration? (m/s²)', '$a = F/m = 4$.', 1, 4.0, 1.0, 'm/s^2', null, []],
        ['numeric', 'A crate has normal force $50\,\mathrm{N}$ and $\mu_k = 0.3$. What is the kinetic friction force in newtons?', '$f_k = \mu_k N = 15\,\mathrm{N}$.', 1, 15.0, 1.0, 'N', null, []],
        ['truefalse', 'Action and reaction forces from Newton\'s third law act on the same object.', 'They act on *different* objects, so they do not cancel.', 1, null, 1.0, '', null, [['True', 0], ['False', 1]]],
    ]);
    $addQuiz(null, 'Final exam: University Physics I', 'final', 60, 30, 3, 5, 900, [
        ['numeric', 'A $2\,\mathrm{kg}$ ball moves at $3\,\mathrm{m/s}$. What is its kinetic energy in joules?', '$K = \tfrac12 m v^2 = 9\,\mathrm{J}$.', 2, 9.0, 1.0, 'J', null, []],
        ['numeric', 'A mass of $1\,\mathrm{kg}$ on a spring with $k = 100\,\mathrm{N/m}$ oscillates. What is its period in seconds?', '$T = 2\pi\sqrt{m/k} = 0.628\,\mathrm{s}$.', 2, 0.6283, 1.0, 's', null, []],
        ['numeric', 'A $0.5\,\mathrm{kg}$ ball moving at $4\,\mathrm{m/s}$ is stopped in $0.1\,\mathrm{s}$. What is the magnitude of the average force in newtons?', '$F = \Delta p/\Delta t = 2/0.1 = 20\,\mathrm{N}$.', 2, 20.0, 1.0, 'N', null, []],
        ['mcq', 'In a perfectly inelastic collision, which quantity is NOT conserved?', 'Momentum is conserved in every isolated collision; kinetic energy is lost.', 1, null, 1.0, '', null, [['kinetic energy', 1], ['momentum', 0], ['total energy', 0]]],
        ['mcq', 'The escape speed from a planet of mass $M$ and radius $R$ is', '$v_{esc}=\sqrt{2GM/R}$.', 1, null, 1.0, '', null, [['$\sqrt{2GM/R}$', 1], ['$\sqrt{GM/R}$', 0], ['$2GM/R$', 0], ['$GM/R^2$', 0]]],
        ['short', 'What is the name of the point where all of a system\'s mass can be treated as concentrated for translational motion?', 'The centre of mass.', 1, null, 1.0, '', "centre of mass\ncenter of mass\ncentre of gravity\ncenter of gravity", []],
        ['truefalse', 'A simple pendulum\'s period (small angles) depends on the mass of the bob.', '$T = 2\pi\sqrt{L/g}$ does not contain $m$.', 1, null, 1.0, '', null, [['True', 0], ['False', 1]]],
        ['numeric', 'A disc of mass $2\,\mathrm{kg}$ and radius $0.5\,\mathrm{m}$ spins about its axis. Find its moment of inertia in $\mathrm{kg\,m^2}$.', '$I = \tfrac12 M R^2 = 0.25$.', 2, 0.25, 1.0, 'kg m^2', null, []],
    ]);

    // ---- "coming soon" courses (shown as teasers; not sample content)
    $soon = [
        ['electricity-and-magnetism', 'Electricity and Magnetism', 'Coulomb to Maxwell: electric fields, circuits and magnetism.', 20],
        ['thermodynamics', 'Thermodynamics', 'Heat, temperature, entropy and the laws of thermodynamics.', 30],
        ['waves-and-optics', 'Waves and Optics', 'Sound, light, interference and diffraction.', 40],
        ['modern-physics', 'Modern Physics', 'Relativity, quantum mechanics and atomic structure.', 50],
    ];
    $insC = $pdo->prepare("INSERT INTO courses (slug, title, subtitle, subject, level, description, status, sort_order, is_sample) VALUES (?, ?, ?, 'Physics', 'University Year 1-2', ?, 'soon', ?, 0)");
    foreach ($soon as [$slug, $title, $sub, $order]) {
        $insC->execute([$slug, $title, $sub, $sub, $order]);
    }

    $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES ('enable_learn', '1') ON DUPLICATE KEY UPDATE svalue = '1'")->execute();
};
