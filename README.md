Installation/upgrade instructions
---------------------------------

1. Make sure you are using the latest stable version of ISPConfig3. (Currently 3.0.2.2)
(If you are using version 3.0.3 and want the forwarding plugin to work follow the instructions for for version 3.0.3)

2. Go to your ISPconfig panel and add a new remote user. (Tab "System" > "Remote users")

Tick

    * Mail domain functions
    * Mail user functions
    * Mail alias functions
    * Mail spamfilter user functions
    * Mail spamfilter policy functions
    * Mail fetchmail functions
    * Mail spamfilter whitelist functions
    * Mail spamfilter blacklist functions
    * Mail user filter functions
    * Client functions
    * Server functions

3. (OPTIONAL IF ABOVE DOESN'T WORK!)
Go to PHPmyAdmin and execute the following MySQL-query on your ispconfig database. Don't forget to edit the remote username in the query.

$ UPDATE `remote_user` SET `remote_functions` = 'mail_domain_get,mail_domain_add,mail_domain_update,mail_domain_delete;mail_user_get,mail_user_add,mail_user_update,mail_user_delete;mail_alias_get,mail_alias_add,mail_alias_update,mail_alias_delete;mail_forward_get,mail_forward_add,mail_forward_update,mail_forward_delete;mail_spamfilter_whitelist_get,mail_spamfilter_whitelist_add,mail_spamfilter_whitelist_update,mail_spamfilter_whitelist_delete;mail_spamfilter_blacklist_get,mail_spamfilter_blacklist_add,mail_spamfilter_blacklist_update,mail_spamfilter_blacklist_delete;mail_spamfilter_user_get,mail_spamfilter_user_add,mail_spamfilter_user_update,mail_spamfilter_user_delete;mail_policy_get,mail_policy_add,mail_policy_update,mail_policy_delete;mail_fetchmail_get,mail_fetchmail_add,mail_fetchmail_update,mail_fetchmail_delete;mail_user_filter_get,mail_user_filter_add,mail_user_filter_update,mail_user_filter_delete;client_get,client_get_id,client_add,client_update,client_delete;server_get' WHERE `remote_user`.`remote_username` = '<<REMOTE USERNAME>>' LIMIT 1 ;

Note! that the SVN version is the most up-to-date version of the plugins and should at any time be preferred over the archived form. Old (archived) versions which are online can harm or misconfigure your system and are NOT supported!

4. Installing
Make sure you are in the plugins directory of your SquirrelMail installation

$ cd plugins

Get the code.

$ svn co https://github.com/w2c/ispconfig3_squirrelmail/trunk ispconfig3

Check that the the ispconfig3 directory is created and set the ownership permissions to your web server user

$ ls
$ chown -R <<webuser>>:<<webgroup>> ispconfig3
$ cd ispconfig3

5. Updating
Make sure you are in the ispconfig3 plugin directory of your SquirrelMail installation

$ cd plugins/ispconfig3
$ svn up .

6.
Copy the file config.php.dist to config.php. 

$ cp config.php.dist config.php

Edit the file and ensure your remote user details are correct:
$ispc_config['remote_soap_user'] = '<<REMOTE USERNAME>>';
$ispc_config['remote_soap_pass'] = '<<REMOTE PASSWORD>>';
$ispc_config['soap_url'] = 'http://<<YOUR SERVER>>:8080/remote/';

Change the port (set to "8080" by default) if necessary and please note that when using SSL to access ISPconfig panel, use "https://" instead of "http://".

For example, if your username is "Santa", your password is "Claus", your server's domain is "christmas.com" (Or you can use an IP), your ISPconfig panel is accessed through port 1111, and you are not using SSL, your configuration would be:

$ispc_config['remote_soap_user'] = 'Santa';
$ispc_config['remote_soap_pass'] = 'Claus';
$ispc_config['soap_url'] = 'http://christmas.com:1111/remote/';

Any module that you want disabled can be remove from enabled_modules. For instance, if you do not wish to give the mail user the ability to change his or her's password you can omit "password" from $ispc_config['enable_modules']. It would then look like this:

$ispc_config['enable_modules'] = array('fetchmail', 'forwarding', 'autoreply', 'mailfilter', 'policy', 'wblist');

7.
Enable the plugin in the SquirrelMail config file. Go to your config directory and run conf.pl. Choose option 8 and move the plugin from the "Available Plugins" category to the "Installed Plugins" category by entering the number next to ispconfig3.  Save and exit.

$ cd ../../config/
$ ./config.pl


8.
A new box should be available in the options page called 'Account'.


Troubleshooting and FAQ
-----------------------

Please see: http://howtoforge.com/forums/showpost.php?p=213239&postcount=20


Current Plugins Status (As last updated instructions revision 12)
---------------------------------------------------------------

    * Account overview (general - always enabled)
    * Fetchmail management (fetchmail)
    * Autoreply management (autoreply)
    * Spamfilter policy and move to junk management (policy)
    * Spamfilter white and black list management (wblist)
    * Password management (password)
    * Mail User Filter management (filter)
    * Forwarding (forwarding)

Current languages supported
---------------------------

Please see: http://howtoforge.com/forums/showpost.php?p=213239&postcount=20


Disclaimer
----------

Neither of the authors of the different parts of the plugins or the installation instructions are responsible for any harm/damage done to your system or any other problems as a result of using these plugins downloaded in either the SVN or any other copy otherwise obtained. By downloading and using the plugins, you agree to the fact that usage of the plugins, it's individual language packs, configuration files and installation instructions is at your sole risk and no responsibility can be taken by any of the authors.

However if any problems do somehow emerge, please do feel free to ask for help here: http://howtoforge.com/forums/showthread.php?p=213239
