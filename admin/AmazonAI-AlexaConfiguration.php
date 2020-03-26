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
	/**
	 * @var AmazonAI_Common
	 */
	private $common;

	/**
	 * AmazonAI_PodcastConfiguration constructor.
	 *
	 * @param AmazonAI_Common $common
	 */
	public function __construct(AmazonAI_Common $common) {
		$this->common = $common;
	}

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
      if ( $this->common->is_podcast_enabled() ) {
        echo '<p class="description">You can extend WordPress websites and blogs through Alexa devices. This opens new possibilities for the creators and authors of websites to reach an even broader audience. It also makes it easier for people to listen to their favorite blogs by just asking Alexa to read them! </p>';
        echo '<img width="500" src="https://d12ee1u74lotna.cloudfront.net/images/alexa1.gif" alt="Alexa Interaction">';

        echo '<p class="description">The following diagram presents the flow of interactions and components that are required to expose your website through Alexa.</p>';
        echo '<img width="500" src="https://d12ee1u74lotna.cloudfront.net/images/alexa2.gif" alt="Alexa Interaction">';

        echo '<p class="description">The following short video presents what the solution works at the end, and how you can ‘listen’ to your blog posts on Alexa devices:</p>';
        echo '<video width="500"  controls><source src="https://d12ee1u74lotna.cloudfront.net/images/AlexaIntegrationSolution.mp4" type="video/mp4"></video>';

        $blog_link = 'https://aws.amazon.com/blogs/machine-learning/read-wordpress-sites-through-amazon-alexa-devices/';
        echo '<p class="description">For more details, and instructions how to connect your site with Alexa, visit following <a target = "_blank" href="' . esc_attr( $blog_link ) . '">AWS AI Blog Post</a></p>';
      } else {
        echo '<p class="description">Amazon Pollycast needs to be enabled</p>';
      }

    }


}
