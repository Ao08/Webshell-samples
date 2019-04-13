<?php
/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2015 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 * @core version 2.6
 * @version 1.0
 */

namespace Model;

class Ticket extends \Core\Model\Model {


    /**
     * 插入用户提交过来的工单内容
     */
    public static function insert() {
        $number = self::isP('number', '请提交您要生成的工单');
        $field = \Model\TicketForm::getFormWithNumber($number);

        $firstContent = current($field);
        if ($firstContent['ticket_status'] == '0') {
            self::error('已关闭该工单的提交');
        }

        $param['ticket_title'] = self::isP('title', '请填写简明扼要的问题描述，以便客户能够快速反馈问题');
        //@todo 联系方式此处需要联动查询，匹配用户选择的联系方式是否存在
        $param['ticket_contact'] = self::isP('contact', '请选择联系方式');
        $param['ticket_contact_account'] = self::isP('contact_account', '请填写您的联系信息');

        //微信的值为3，所以不需要验证数据格式
        if (\Model\Extra::checkInputValueType($param['ticket_contact_account'], $param['ticket_contact']) === false && $param['ticket_contact'] != 3 ) {
            self::error('您填写联系方式的信息格式不正确。');
        }

        //工单长度限定为15
        $param['ticket_number'] = str_pad(substr(\Model\Extra::getOnlyNumber(), 0, 15), 15, 0, STR_PAD_RIGHT);
        $param['ticket_model_id'] = $firstContent['ticket_model_id'];
        $param['ticket_submit_time'] = time();
        $param['member_id'] = empty(self::session()->get('member')) ? '-1' : self::session()->get('member')['member_id'];

        if ($firstContent['ticket_model_verify'] == '1') {
            $verify = self::isP('verify', '请填写验证码');
            if (md5($verify) != self::session()->get('verify')) {
                self::error('验证码错误');
            }
        }


        self::db()->transaction();

        $createTicket = self::db('ticket')->insert($param);
        if ($createTicket === false) {
            self::db()->rollback();
            self::error('创建工单失败');
        }

        self::insertContent($createTicket, $field);
        self::newTicketNotice($firstContent, $param);

        self::db()->commit();
        $domain = \Model\Content::findContent('option', 'domain', 'option_name');
        return $param;
    }

    /**
     * 记录提交的工单表单内容
     * @param $ticketID 创建的工单ID
     * @param $field 对应工单的字段
     */
    private static function insertContent($ticketID, $field) {
        $formID = [];
        array_walk($field, function ($value) use (&$formID) {
            $formID[$value['ticket_form_id']] = $value['ticket_form_name'];
        });

        foreach ($field as $key => $value) {
            $form = self::p($value['ticket_form_name']);
            if (is_array($form)) {
                $form = implode(',', $form);
            }

            if ($value['ticket_form_required'] == '1' || $value['ticket_form_bind'] > 0) {
                $msg = empty($value['ticket_form_msg']) ? "{$value['ticket_form_description']}为必填项" : $value['ticket_form_msg'];

                if (empty($form) && !is_numeric($form) && !is_string($form) && $value['ticket_form_bind'] == 0) {
                    self::error($msg);
                } elseif ($value['ticket_form_bind'] > 0 && in_array($_POST[$formID[$value['ticket_form_bind']]], explode(',', $value['ticket_form_bind_value'])) && $value['ticket_form_required'] == '1' && empty($form) && !is_numeric($form) && !is_string($form)) {
                    self::error($msg);
                }
            }

            //@todo 这里还差一个工单表单类型验证功能

            $result = self::db('ticket_content')->insert(['ticket_id' => $ticketID, 'ticket_form_id' => $value['ticket_form_id'], 'ticket_form_content' => $form]);
            if ($result === false) {
                self::db()->rollback();
                self::error('记录工单内容出错');
            }

        }

    }

    /**
     * 新工单消息
     * @param $ticket 工单基础信息
     * @param $param 内容参数
     */
    private static function newTicketNotice($ticket, $param){

        //将工单单号发给发起者
        \Model\Notice::addTicketNoticeAction($param['ticket_number'], $param['ticket_contact_account'], $param['ticket_contact'], 1);

        //新工单后台客服通知
        if(!empty($ticket['ticket_model_group_id'])){
            //移除手尾,
            $ticket['ticket_model_group_id'] = trim($ticket['ticket_model_group_id'], ',');

            $userList = self::db('user')->where("user_group_id IN ({$ticket['ticket_model_group_id']})")->select();
            if(!empty($userList)){
                foreach ($userList as $user){
                    \Model\Notice::addCSNotice($param['ticket_number'], $user, -1);
                }
            }
        }
    }

    /**
     * 获取用户提交的工单表单内容
     * @param $id 工单ID
     * @return 返回查询得到的内容
     */
    public static function getTicketContent($id) {
        $form = [];
        $result = self::db('ticket_content AS tc')->join(self::$modelPrefix . 'ticket_form AS tf ON tf.ticket_form_id = tc.ticket_form_id')->where('tc.ticket_id = :ticket_id')->order('tf.ticket_form_listsort ASC, tf.ticket_form_id DESC')->select(['ticket_id' => $id]);
        if (empty($result)) {
            return $form;
        }
        //组装一下，让他ticket_form_id成为键值
        foreach ($result as $value) {
            $form[$value['ticket_form_id']] = $value;

            switch ($value['ticket_form_type']){
                case 'radio':
                case 'checkbox':
                case 'select':
                case 'checkbox':
                    //获取相应工单字段的选项值
                    $form[$value['ticket_form_id']]['ticket_form_option'] = json_decode(htmlspecialchars_decode($value['ticket_form_option']), true);
                    //复选项要做特殊处理
                    if($value['ticket_form_type'] == 'checkbox'){
                        $ticketValue = [];
                        foreach (explode(',', $value['ticket_form_content']) as $item){
                            $ticketValue[] = array_search($item, $form[$value['ticket_form_id']]['ticket_form_option']);
                        }
                        $form[$value['ticket_form_id']]['ticket_value'] = implode(' , ', $ticketValue);
                    }else{
                        $form[$value['ticket_form_id']]['ticket_value'] = array_search($value['ticket_form_content'], $form[$value['ticket_form_id']]['ticket_form_option']);
                    }


                    break;
                case 'thumb':
                    $form[$value['ticket_form_id']]['ticket_value'] = empty($value['ticket_form_content']) ? '' : '<img src="'.$value['ticket_form_content'].'" alt="'.$value['ticket_form_content'].'" class="am-img-thumbnail" height="300" />';
                    break;
                case 'img':
                    $splitImg = explode(',', $value['ticket_form_content']);
                    $imgStr = '<ul class="am-avg-sm-4 am-thumbnails">';
                    if(!empty($value['ticket_form_content'])){
                        foreach ($splitImg as $item){
                            $imgStr .= '<li><img src="'.$item.'" alt="'.imgs.'" class="am-img-thumbnail" /></li>';
                        }
                    }
                    $imgStr .= '</ul>';
                    $form[$value['ticket_form_id']]['ticket_value'] = $imgStr;
                    break;
                case 'file':
                    //@todo 待优化
                    $splitImg = explode(',', $value['ticket_form_content']);
                    $imgStr = '<ul class="am-avg-sm-4 am-thumbnails">';
                    if(!empty($value['ticket_form_content'])){
                        foreach ($splitImg as $key => $item){
                            $imgStr .= '<li><a href="'.$item.'">下载附件'.($key +1) .'</a></li>';
                        }
                    }
                    $imgStr .= '</ul>';
                    $form[$value['ticket_form_id']]['ticket_value'] = $imgStr;
                    break;
                default:
                    $form[$value['ticket_form_id']]['ticket_value'] = (new \voku\helper\AntiXSS())->xss_clean(htmlspecialchars_decode( $value['ticket_form_content'] ));
            }

            if ($value['ticket_form_bind'] > 0) {
                $form[$value['ticket_form_id']]['ticket_form_bind_value'] = explode(',', $value['ticket_form_bind_value']);
            }
        }

        return $form;
    }

    /**
     * 查看工单内容
     * 注：前后台公用本方法
     * @return array 返回处理好得通用数组
     */
    public static function view() {
        $number = self::isG('number', '请选择您要查看的工单');
        $ticket = self::getTicketBaseInfo($number);
        if (empty($ticket)) {
            return false;
        }
        $form = self::getTicketContent($ticket['ticket_id']);
        $chat = self::getTicketChat($ticket['ticket_id']);

        return ['ticket' => $ticket, 'form' => $form, 'chat' => $chat];

    }

    /**
     * 获取工单的基础信息
     * @param $number
     * @return mixed
     */
    public static function getTicketBaseInfo($number){
        return self::db('ticket AS t')->join(self::$modelPrefix.'ticket_model AS tm ON tm.ticket_model_id = t.ticket_model_id')->where('ticket_number = :ticket_number')->find([
            'ticket_number' => $number
        ]);
    }

    /**
     * 获取客服与用户的反馈信息
     * @param $id 工单ID
     * @return 返回查询得到的内容
     */
    public static function getTicketChat($id) {
        $sql = "SELECT %s FROM " . self::$modelPrefix . "ticket_chat WHERE ticket_id = :ticket_id ORDER BY ticket_chat_id ASC";
        return \Model\Content::quickListContent(['count' => sprintf($sql, 'count(*)'), 'normal' => sprintf($sql, '*'), 'param' => ['ticket_id' => $id], 'page' => 30]);
    }

    /**
     * 回复工单
     * @param $id 工单ID
     * @param $content 内容
     * @param string $custom 本参数用于识别前台还是后台，默认为空。
     * @return mixed
     */
    public static function addReply($id, $content, $custom = '') {
        $param = ['ticket_id' => $id, 'ticket_chat_content' => $content, 'ticket_chat_time' => time()];
        if (!empty(self::session()->get('ticket')) && empty($custom)) {
            $param['user_id'] = self::session()->get('ticket')['user_id'];
            $param['user_name'] = self::session()->get('ticket')['user_name'];
        }

        return self::db('ticket_chat')->insert($param);

    }

    /**
     * 设置工单负责人
     * @param $id 工单ID
     * @param $userID 用户的ID
     * @param $userName 用户名称
     * @description 此处需要手动填写用户的ID和名称是由于，除了当前的客户外，还会有一个转指派的。这时候他就需要手动声明用户信息了。
     */
    public static function setUser($id, $userID, $userName) {
        return self::inTicketIdWithUpdate(['user_id' => $userID, 'user_name' => $userName, 'noset' => ['ticket_id' => $id]]);
    }

    /**
     * 更改任务状态
     * @param $id 工单ID
     * @param $status 要更改的状态
     */
    public static function changeStatus($id, $status) {
        return self::inTicketIdWithUpdate(['ticket_status' => $status, 'noset' => ['ticket_id' => $id]]);
    }

    /**
     * 更新工单耗时参照时间
     * @param $id
     * @return mixed
     */
    public static function updateReferTime($id) {
        return self::inTicketIdWithUpdate(['ticket_refer_time' => time(), 'noset' => ['ticket_id' => $id]]);
    }

    /**
     * 工单运行时间
     * @param $id 工单ID
     * @param $submitTime 工单耗时参照时间
     * @return mixed
     */
    public static function runTime($id, $referTime, $runTime) {
        $runTime = (time() - $referTime) + $runTime;
        return self::inTicketIdWithUpdate(['ticket_run_time' => $runTime, 'noset' => ['ticket_id' => $id]]);
    }

    /**
     * 依据工单ID进行更新
     * @param array $param
     * @return mixed
     */
    public static function inTicketIdWithUpdate(array $param) {
        return self::db('ticket')->where('ticket_id = :ticket_id')->update($param);
    }

    public static function loginCheck($ticket, $back_url = ''){

        if(empty($back_url)){
            $back_url = base64_encode($_SERVER['REQUEST_URI']);
        }

        //判断工单模型是否设置登录验证.
        if($ticket['ticket_model_login'] == 1 && empty(self::session()->get('member'))){
            self::success('需要登录帐号', self::url('Login-index', ['back_url' => $back_url]), -1);
        }

        //非匿名工单判断用户所属，非此用户所属则跳转至我的工单
        if($ticket['member_id'] != '-1' && $ticket['member_id'] != self::session()->get('member')['member_id'] ){
            self::success('获取工单成功，系统将指引您返回工单列表', self::url('Member-index'), -1);
        }
    }

}