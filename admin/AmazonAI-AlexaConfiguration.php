<?php
/**
 * Class responsible for providing GUI for Alexa integration configuration.
 *
 * @link       amazon.com
 * @since      2.5.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */
class AmazonAI_AlexaConfiguration
{

    public function amazon_ai_add_menu()
    {
        $this->plugin_screen_hook_suffix = add_submenu_page('amazon_ai', 'Alexa Integration', 'Alexa Integration', 'manage_options', 'amazon_ai_alexa', array(
            $this,
            'amazonai_gui'
        ));
    }

    public function amazonai_gui()
    {
?>
			 <div class="wrap">
			 <div id="icon-options-alexa" class="icon32"></div>
			 <h1>Alexa Integration</h1>
			 <form method="post" action="options.php">


	 </div>
	 <?php
    }


}
