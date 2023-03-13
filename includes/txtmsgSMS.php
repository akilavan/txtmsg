<?php

class txtmsgSMS
{

    public $prefix = 'txtmsg_sms_woo_';

    public function __construct($baseFile = null)
    {
        $this->init();
    }

    private function init()
    {

        $sMSEvents = new txtmsgSMSEvents();
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab') , 50);
        add_action('woocommerce_settings_tabs_settings_tab_txtmsg', array($this, 'settings_tab'));
        add_action('woocommerce_update_options_settings_tab_txtmsg', array($this, 'update_settings'));
        add_action('woocommerce_order_status_processing', array($sMSEvents, 'send_admin_sms_for_new_order'), 10, 1);
    }

    public function add_settings_tab($settings_tabs)
    {
        $settings_tabs['settings_tab_txtmsg'] = __('txtmsg.lk SMS Gateway', $this->prefix);
        return $settings_tabs;
    }

    public function update_settings()
    {
        woocommerce_update_options($this->getFields());
    }

    public function settings_tab()
    {
        woocommerce_admin_fields($this->getFields());
    }

    private function getFields()
    {

        $all_statusses = wc_get_order_statuses();
		
	
		?>
        <div class="wrap">
			<img src="https://asit.pw/logotxtmsg.png" style='width:150px'/>
		</div>
		<?php

		/*
         * 
         * API Access Setting
         * 
         */

        $fields[] = array('type' => 'sectionend', 'id' => $this->prefix . 'apisettings');
        $fields[] = array(
            'title' => __('txtmsg.lk SMS Settings', $this->prefix),
            'type' => 'title',
            'desc' => 'Provide following details from your txtmsg.lk SMS gateway account.<br/> <a href="https://sms.txtmsg.lk/developers" target="_blank">Click here</a> to see your API Token. If you dont have access <a href="https://sms.txtmsg.lk/register">signup</a> and request a free trial',
            'id' => $this->prefix . 'txtmsg_settings'
        );
        $fields[] = array(
            'title' => __('API Token', $this->prefix),
            'id' => $this->prefix . 'api_token',
            'desc_tip' => __('API Token of your txtmsg.lk SMS account.', $this->prefix),
            'type' => 'text',
            'css' => 'min-width:300px;',
        );
        $fields[] = array(
            'title' => __('Sender ID', $this->prefix),
            'id' => $this->prefix . 'sender_id',
            'desc_tip' => __('Enter your Sender ID (Mask) purchased from txtmsg.lk.', $this->prefix),
            'type' => 'text',
            'css' => 'min-width:300px;',
        );

		
		/*
         * Shortcodes and its descriptions.
         */
		$fields[] = array('type' => 'sectionend', 'id' => $this->prefix . 'apisettings');
        $avbShortcodes = array(
            '{{first_name}}' => "First name of the customer.",
            '{{last_name}}' => "Last name of the customer.",
            '{{shop_name}}' => 'Your shop name (' . get_bloginfo('name') . ').',
            '{{order_id}}' => 'Order ID.',
            '{{order_amount}}' => "Current order amount.",
            '{{order_status}}' => 'Current order status (Pending, Failed, Processing, etc...).',
            '{{billing_city}}' => 'Available city in the customer billing address',
            '{{customer_phone}}' => 'Given customer phone number.'
        );

        $shortcode_desc = '';
        foreach ($avbShortcodes as $handle => $description) {
            $shortcode_desc .= '<b>' . $handle . '</b> - ' . $description . '<br>';
        }

        $fields[] = array(
            'title' => __('Available Shortcodes', $this->prefix),
            'type' => 'title',
            'desc' => 'These shortcodes can be used in your message body contents. <br><br>' . $shortcode_desc,
            'id' => $this->prefix . 'txtmsg_settings'
        );
		

        $fields[] = array(
            'title' => 'Default Message Containt(If a message not set)',
            'id' => $this->prefix . 'default_sms_containt',
            'desc_tip' => __('This message will be sent if there are no any message text in the event message fields.', $this->prefix),
            'default' => __('Dear Customer, Your order #{{order_id}} is now {{order_status}}. Thank you for shopping at {{shop_name}}.', $this->prefix),
            'type' => 'textarea',
            'css' => 'min-width:500px;min-height:120px;'
        );

        foreach ($all_statusses as $key => $val) {
            $key = str_replace("wc-", "", $key);
            $fields[] = array(
                'title' => $val,
                'desc' => 'Enable "' . $val . '" status alert',
                'id' => $this->prefix . 'send_sms_' . $key,
                'default' => 'yes',
                'type' => 'checkbox',
            );
            $fields[] = array(
                'id' => $this->prefix . $key . '_sms_template',
                'type' => 'textarea',
                'placeholder' => 'SMS Content for the ' . $val . ' event',
                'css' => 'min-width:500px;margin-top:-25px;min-height:120px;'
            );
        }


        $fields[] = array(
            'type' => 'sectionend', 
            'id' => $this->prefix . 'notesettings');
        $fields[] = array(
            'title' => 'Customer Note SMS',
            'type' => 'title',
            'desc' => 'Enable Sending SMS for new customer notes.',
            'id' => $this->prefix . 'notesettings'
        );

        $fields[] = array(
            'title' => 'Send Notes SMS',
            'id' => $this->prefix . 'enable_notes_sms',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => 'Enable Sending SMS for new customer notes'
        );

        $fields[] = array(
            'title' => 'Note Message Prefix',
            'id' => $this->prefix . 'note_sms_template',
            'desc_tip' => 'This Text will be prepended to your customer note.',
            'css' => 'min-width:500px;',
            'default' => 'You have a new note: ',
            'type' => 'textarea'
        );


        /*
         * 
         * Admin SMS
         * 
         */

        $fields[] = array(
            'type' => 'sectionend', 
            'id' => $this->prefix . 'adminsettings
        ');

        $fields[] = array(
            'title' => 'SMS for Admin',
            'type' => 'title',
            'desc' => 'Enable sending admin sms for new customer orders.',
            'id' => $this->prefix . 'adminsettings'
        );

        $fields[] = array(
            'title' => 'Receive Admin SMS',
            'id' => $this->prefix . 'enable_admin_sms',
            'desc' => 'Enable sending admin sms for new customer orders.',
            'default' => 'no',
            'type' => 'checkbox'
        );

        $fields[] = array(
            'title' => 'Admin Mobile Number',
            'id' => $this->prefix . 'admin_sms_recipients',
            'desc' => 'Enter admin mobile numbers. You can use multiple numbers separated by comma.<br> Example: 07123456789, 0726520000.',
            'desc_tip' => 'Enter admin mobile numbers. You can use multiple numbers separated by comma.<br> Example: 07123456789, 0726520000',
            'default' => '',
            'type' => 'text'
        );

        $fields[] = array(
            'title' => 'Message',
            'id' => $this->prefix . 'admin_sms_containt',
            'desc_tip' => 'Customization tags for new order SMS: {{shop_name}}, {{order_id}}, {{order_amount}}.',
            'css' => 'min-width:500px;',
            'default' => 'Newcustomer order received at {{shop_name}}. Order #{{order_id}}, Total Value: {{order_amount}}',
            'type' => 'textarea'
        );

         
        $fields[] = array('type' => 'sectionend', 'id' => $this->prefix . 'customersettings');

        return $fields;
    }
	
}
