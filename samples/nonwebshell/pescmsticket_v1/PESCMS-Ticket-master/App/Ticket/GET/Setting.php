<?php
/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2014 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 * @core version 2.6
 * @version 1.0
 */

namespace App\Ticket\GET;

class Setting extends \Core\Controller\Controller {


    public function action(){
        $option = [];
        foreach(\Model\Content::listContent(['table' => 'option']) as $key => $value){
            if(is_array(json_decode($value['value'], true)) || $value['option_name'] == 'crossdomain' ){
                $option[$value['option_name']] = json_decode($value['value'], true);
            }else{
                $option[$value['option_name']] = $value;
            }
        }
        $this->assign($option);
        $this->assign('title', '系统设置');
        $this->layout();
    }

	/**
	 * 邮件发送测试
	 */
	public function emailTest(){
		$email = $this->isG('email', '请提交邮件地址');
		if(\Model\Extra::checkInputValueType($email, 1) === false){
			$this->error('请提交正确的邮件地址');
		}
		(new \Expand\Notice\Mail())->test($email);
	}

    public function upgrade(){
        $this->assign('title', '检查更新');
        $this->layout();
    }

    /**
     * 短信测试
     */
    public function mobileTest(){
        $mobile = $this->isG('mobile', '请输入手机号码');
        $id = $this->isG('template', '请选择模板');

        $viewTicketLinke = \Model\MailTemplate::getViewLink('123456');

        $template = \Model\MailTemplate::matchContent(['number' => '123456', 'content' => '测试的内容', 'view' => $viewTicketLinke], $id);

        $result = (new \Expand\sms())->send([
            'send_id' => -1,
            'send_account' => $mobile,
            'send_title' => $template['sms']
        ]);
        echo "<p>当前发送模板: {$template['sms']}</p>";
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        echo '<br/>';
        exit;

    }

}