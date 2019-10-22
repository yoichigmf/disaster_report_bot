<?php

/**
 * 指定されたInfoに基づき、Slackのチャネルにメッセージをポストするクラス
 */
class SlackBot
{
    /**
     * リクエスト用のオプションを設定
     */
    protected function create_options($info)
    {
        return array(
            CURLOPT_URL            => $info['url'],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $info['body'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
        );
    }

    /**
     * リクエストを実行
     */
    protected function request($options)
    {
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result      = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = substr($result, 0, $header_size);
        $result      = substr($result, $header_size);
        curl_close($ch);

        return array(
            'Header' => $header,
            'Result' => $result,
        );
    }

    /**
     * メッセージをポストする
     */
    public function post_message($info)
    {
        return $this->request($this->create_options($info->get_post_info()));
    }
}
