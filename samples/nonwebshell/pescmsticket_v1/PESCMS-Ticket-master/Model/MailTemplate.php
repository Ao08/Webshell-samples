<?php
/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2016 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */
namespace Model;


class MailTemplate extends \Core\Model\Model {

    /**
     * 模板类型
     * 新工单|1
     * 受理工单|2
     * 回复工单|3
     * 转交客服|4
     * 工单完成|5
     * 工单关闭|6
     * @var array
     */
    private static $templateType = [];

    /**
     * 依据模板类型，获取模板
     * @param $type
     * @return mixed
     */
    public static function getTemplate($type) {
        if (empty(self::$templateType[$type])) {
            self::$templateType[$type] = \Model\Content::findContent('mail_template', $type, 'mail_template_type');
        }
        return self::$templateType[$type];
    }

    /**
     * 快速生成查看工单的链接
     * @param $number 工单号
     * @return string
     */
    public static function getViewLink($number) {
        $system = \Core\Func\CoreFunc::$param['system'];
        $link = $system['domain'] . self::url('Form-View-ticket', ['number' => $number]);
        return '<a href="' . $link . '">' . $link . '</a>';
    }

    /**
     * 快速生成客服查看工单的链接
     * @param $number 工单号
     * @return string
     */
    public static function getCSViewLink($number) {
        $system = \Core\Func\CoreFunc::$param['system'];
        $link = $system['domain'] . self::url('Ticket-Ticket-handle', ['number' => $number]);
        return '<a href="' . $link . '">' . $link . '</a>';
    }

    /**
     * 匹配邮件模板标题
     * @param $number 单号
     * @param $type 模板类型
     * @return mixed
     */
    public static function matchTitle($number, $type) {
        $template = self::getTemplate($type);
        $title = str_replace('{number}', $number, $template['mail_template_title']);
        $data = [
            '1' => $title,
            '2' => $title,
            '3' => $template['mail_template_weixin_template_id']
        ];
        return $data;
    }

    /**
     * 匹配邮件模板内容
     * @param array $param 参数
     * @param $type 模板类型
     * @return mixed
     */
    public static function matchContent(array $param, $type) {
        $param = array_merge(['number' => '', 'view' => ''], $param);
        $template = self::getTemplate($type);
        foreach ([
                    '1' => 'mail_template_content',
                    '2' => 'mail_template_sms',
                    '3' => 'mail_template_weixin_template'
                 ] as $key => $item){

            if(in_array($key, [2, 3])){
                $param['view'] = strip_tags($param['view']);
            }

            //微信通知需要先将内容格式化，补充通知的超链接。
            if($key == 3){
                $newFormat = [
                    'data' => json_decode(htmlspecialchars_decode($template[$item]), true),
                    'link' => strip_tags($param['view'])
                ];
                $template[$item] = json_encode($newFormat);
            }

            $content[$key] = str_replace(
                '{number}',
                $param['number'],
                str_replace(
                    '{view}',
                    $param['view'],
                    $template[$item]
                )
            );
        }

        return $content;
    }

}