<?php

/**
 * Slackへのポスト情報を担うクラス
 */
class SlackBotInfo
{
    /** ポスト先の部屋 */
    public $channel = '#bothook_test';
    /** botのお名前 */
    public $username = 'my_bot';
    /** botのアイコン */
    public $icon_emoji = ':ghost:';
    /** ポストするメッセージ */
    protected $message = '';
    /** ポスト先チャネルのURL */
    protected $url = '';

    /**
     * コンストラクタ
     */
    public function __construct($url = '', $message = '')
    {
        $this->set_url($url);
        $this->set_message($message);
    }

    /**
     * ポスト先チャネルのURLを設定する
     */
    public function set_url($url)
    {
        $this->url = $url;
    }

    /**
     * ポストするメッセージを設定する
     */
    public function set_message($message)
    {
        $this->message = $message;
    }

    /**
     * Slackへのポスト情報を返す
     */
    public function get_post_info()
    {
        return array(
            'url'  => $this->url,
            'body' => array(
                'payload' => json_encode(array(
                    'channel'    => $this->channel,
                    'username'   => $this->username,
                    'icon_emoji' => $this->icon_emoji,
                    'text'       => $this->message,
                )),
            ),
        );
    }
}
