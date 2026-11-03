<section class="page-hero"><div class="wrap"><h1>My account</h1></div></section>
<section class="section">
  <div class="wrap">
    <?php partial('account_tabs', ['tab' => $tab]); ?>
    <div class="narrow-wide" style="max-width:820px">
      <?php partial('security_2fa', get_defined_vars()); ?>
    </div>
  </div>
</section>
