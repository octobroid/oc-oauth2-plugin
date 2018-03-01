<?php namespace Octobro\OAuth2\Updates;

use October\Rain\Database\Updates\Migration;

/**
 * Copy the oAuth 2 config file to root config.
 *
 */
class PublishConfigFile extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        copy(plugins_path('octobro/oauth2/config/oauth2.php'), config_path('oauth2.php'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (file_exists(config_path('oauth2.php')))
            unlink(config_path('oauth2.php'));
    }
}
