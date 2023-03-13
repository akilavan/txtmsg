<?php

class txtmsgSMSEvents
{
    /*
     * Declare prefix and other values.
     */

    private $prefix = 'txtmsg_sms_woo_';
    private $apiToken;
	private	$senderId;
	private $adminnumbers;
    private $enableAdminSms;
    private $defaultContent; 
	private $adminDefaultContent;

    public function __construct()
    {
        /*
         * Get txtmsg.lk configuration settings in woocommerce admin panel.
         */
        $this->apiToken = get_option($this->prefix . 'api_token');
        $this->senderId = get_option($this->prefix . 'sender_id');
        $this->adminnumbers = get_option($this->prefix . 'admin_sms_recipients');

        
		/*
         * admin sms enabled or desabled.
         */
        $this->enableAdminSms = get_option($this->prefix . 'enable_admin_sms') == 'yes';

		
        /*
         * trigger sms
         */
        $this->defaultContent = get_option($this->prefix . 'default_sms_containt');
        $this->adminDefaultContent = get_option($this->prefix . 'admin_sms_containt');

        add_action('woocommerce_order_status_changed', array($this, 'send_sms_for_events'), 11, 3);
        add_action('woocommerce_new_customer_note', array($this, 'send_new_order_note'));
		
    }

    public function send_sms_for_events($order_id, $from_status, $to_status)
    {
        if (get_option($this->prefix . 'send_sms_' . $to_status) !== "yes")
            return;
        $this->txtmsgSmsSend($order_id, $to_status);
    }

    public function send_admin_sms_for_new_order($order_id)
    {
        if ($this->enableAdminSms)
            $this->txtmsgSmsSend($order_id, 'admin-order');
    }

    public function send_new_order_note($data)
    {
        if (get_option($this->prefix . 'enable_notes_sms') !== "yes")
            return;

        $this->txtmsgSmsSend($data['order_id'], 'new-note', $data['customer_note']);
    }

    public static function shortCode($message, $order_details)
    {
        $replacements_string = array(
            '{{shop_name}}' => get_bloginfo('name'),
            '{{order_id}}' => $order_details->get_order_number(),
            '{{order_amount}}' => $order_details->get_total(),
            '{{order_status}}' => ucfirst($order_details->get_status()),
            '{{first_name}}' => ucfirst($order_details->billing_first_name),
            '{{last_name}}' => ucfirst($order_details->billing_last_name),
            '{{billing_city}}' => ucfirst($order_details->billing_city),
            '{{customer_phone}}' => $order_details->billing_phone,
        );
        return str_replace(array_keys($replacements_string), $replacements_string, $message);
    }

    private function txtmsgSmsSend($order_id, $status, $message_text = '')
    {
        $order_details = new WC_Order($order_id);
        $message = '';

        if ($status == 'admin-order') {
            $message = $this->adminDefaultContent;
        } elseif ($status == 'new-note') {
            $message_prefix = get_option($this->prefix  . 'note_sms_template');
            $message = $message_prefix .  $message_text;
        } else {
            $message = get_option($this->prefix . $status . '_sms_template');
            if (empty($message))
                $message = $this->contentDefault;
        }

        $message = (empty($message) ? $this->contentDefault : $message);
        $message = self::shortCode($message, $order_details);

        $fName = $order_details->billing_first_name;
        $lName = $order_details->billing_last_name;
        $bEmail = $order_details->billing_email;
        $addr1 = $order_details->billing_address_1;
        $addr2 = $order_details->billing_address_2;
        $bCity = $order_details->billing_city;
        $postC = $order_details->shipping_postcode;
        $address = $addr1 . ', ' . $addr2 . ', ' . $bCity . ', ' . $postC;

        $pn = ('admin-order' === $status ? $this->adminnumbers : $order_details->billing_phone);

        $to_numbers = explode(',', $pn);
        foreach ($to_numbers as $numb) {
            if (empty($numb))
                continue;

            $phone = preg_replace('/^(?:\+94|94|0|940)?/','94', ltrim($numb));
          
          
         //   $apiInt->sendSMS($this->apiToken, $message, $phone, $this->senderId);
          
          if ($this->apiToken === null) {
            throw new \InvalidArgumentException('Required parameter $apiKey Missing - txtmsg.lk SMS');
        }

        if ($message === null) {
            throw new \InvalidArgumentException('Required parameter $message Missing- txtmsg.lk SMS');
        }

        if ($phone === null) {
            throw new \InvalidArgumentException('Required parameter $to Missing- txtmsg.lk SMS');
        }

        if ($this->senderId === null) {
            throw new \InvalidArgumentException('Required parameter $senderId Missing- txtmsg.lk SMS');
        }

        // make the API Call
	    $MESSAGE = urldecode($message);
	    $AUTH = $this->apiToken;
        $SENID = $this->senderId;
        $PHN=$phone;

	    $msgdata = array("recipient"=>$PHN, "sender_id"=>$SENID, "message"=>$MESSAGE);	
        $curl = curl_init();
        
        //IF you are running in locally and if you don't have https/SSL. then uncomment bellow two lines
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://sms.txtmsg.lk/api/v3/sms/send",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($msgdata),
            CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "authorization: Bearer $AUTH",
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

			if ($err) {
				throw new \Exception($err); 
			}
        }
		return;
    }
	
	
	private function txtmsgSmsBalance(){
  
			
            $url = "https://sms.txtmsg.lk/api/v3/balance";
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            
            $headers = array(
               "Accept: application/json",
               "Authorization: Bearer ".$this->apiToken,
            );
            
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            //for debug only!
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            
            $result = curl_exec($curl);
            
			curl_close ($ch);
			$acc_details = json_decode($result,true);
       
        //response handilng
        if($acc_details['status']=='success'){
            if($acc_details['data']['remaining_unit']>0){
            $response = 'SMS Balance: Rs. ' . $acc_details['data']['remaining_unit'];
            }else{
                $response =  "Your account balance is too low . Please Reacharge your account.";
            }
            
        }else{
            
                $response = 'Please check your credentils. Error : '.$acc_details['message'];
        }
        
    }
	
	
}
