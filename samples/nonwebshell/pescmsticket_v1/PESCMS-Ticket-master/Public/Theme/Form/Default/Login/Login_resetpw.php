<div class="am-input-group am-margin-bottom">
    <span class="am-input-group-label"><i class="am-icon-lock am-icon-fw"></i></span>
    <input type="password" name="passwd" class="am-form-field" placeholder="密码" required="required">
</div>

<div class="am-input-group am-margin-bottom">
    <span class="am-input-group-label"><i class="am-icon-lock am-icon-fw"></i></span>
    <input type="password" name="repasswd" class="am-form-field" placeholder="确认密码" required="required">
</div>

<div class="am-input-group am-margin-bottom">
    <span class="am-input-group-label"><i class="am-icon-shield am-icon-fw"></i></span>
    <input type="text" class="am-form-field login-verify" name="verify" placeholder="验证码" maxlength="7">
    <img src="<?= $label->url('Index-verify') ?>" class="refresh-verify">
</div>