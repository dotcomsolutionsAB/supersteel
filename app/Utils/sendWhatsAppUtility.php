<?php

namespace App\Utils;

use Illuminate\Support\Facades\Http;

class sendWhatsAppUtility 
{
    public static function sendWhatsApp($customer, $params, $media, $campaignName) 
    {
        if (env('WHATSAPP_SERVICE_ON')) 
        {

            $content = array();
            $content['messaging_product'] = "whatsapp";
            $content['to'] = $customer;
            $content['type'] = 'template';
            $content['biz_opaque_callback_data'] = $campaignName;
            $content['template'] = $params;

            $token = env('WHATSAPP_API_TOKEN');

            $curl = curl_init();

            // Initialize $response to a default value
            // changes due to server issue
            $response = null;

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://graph.facebook.com/v19.0/357370407455461/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($content),
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

        }
        return $response;
    }

    public static function sendWhatsAppApprove($customer, $params, $media, $campaignName) 
    {
        if (env('WHATSAPP_SERVICE_ON')) 
        {

            $content = array();
            $content['messaging_product'] = "whatsapp";
            $content['to'] = $customer;
            $content['type'] = 'template';
            $content['biz_opaque_callback_data'] = 'ace_commercial';
            $content['template'] = $params;

            $token = env('WHATSAPP_API_TOKEN');

            $curl = curl_init();

            // Initialize $response to a default value
            // changes due to server issue
            $response = null;

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://graph.facebook.com/v19.0/357370407455461/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($content),
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

        }
        return $response;
    }

}