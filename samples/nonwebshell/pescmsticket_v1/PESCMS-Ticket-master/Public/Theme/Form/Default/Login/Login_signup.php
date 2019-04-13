<div class="am-input-group am-margin-bottom">
    <span class="am-input-group-label"><i class="am-icon-navicon am-icon-fw"></i></span>
    <input type="text" maxlength="10" name="name" class="am-form-field" placeholder="用户名称" required="required">
</div>

<div class="am-input-group am-margin-bottom">
    <span class="am-input-group-label"><i class="am-icon-envelope am-icon-fw"></i></span>
    <input type="email" name="email" class="am-form-field" placeholder="邮箱地址" required="required">
</div>

<div class="am-input-group am-margin-bottom">
    <span class="am-input-group-label"><i class="am-icon-phone am-icon-fw"></i></span>
    <input type="text" name="phone" class="am-form-field" placeholder="手机号码" required="required">
</div>


<div class="am-input-group am-margin-bottom">
    <span class="am-input-group-label"><i class="am-icon-lock am-icon-fw"></i></span>
    <input type="password" name="password" class="am-form-field" placeholder="密码" required="required">
</div>

<div class="am-input-group am-margin-bottom">
    <span class="am-input-group-label"><i class="am-icon-lock am-icon-fw"></i></span>
    <input type="password" name="repassword" class="am-form-field" placeholder="确认密码" required="required">
</div>

<div class="am-input-group am-margin-bottom">
    <span class="am-input-group-label"><i class="am-icon-shield am-icon-fw"></i></span>
    <input type="text" class="am-form-field login-verify" name="verify" placeholder="验证码" maxlength="7">
    <img src="<?= $label->url('Index-verify') ?>" class="refresh-verify">
</div>