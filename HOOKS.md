This plugin has a number of hooks that you can use, as developer or as a user, to customize the user experience or to give access to extended functionalities.

## Sessions terminations actions
If you want to take action - like informing administrator, displaying message to user, etc. - while sessions are or have been terminated, you can use `sessions_force_terminate`, `sessions_after_idle_terminate`, `sessions_after_expired_terminate` and `sessions_force_admin_terminate` actions.

### Example
Warn someone by mail in case of idle session terminated:
```php
  add_filter(
    'sessions_after_idle_terminate',
    function( $user_id ) {
      wp_mail( 'someone@example.com', 'Idle session terminated', sprintf( 'The session for user ID%d has been terminated because it was idle.', $user_id) );
    }
  );
```

## Customization of messages
Messages that are shown to users when they are not allowed to initiate a new session are set in main plugin settings. But they can be overridden on a case-by-case basis with the two following filters:

* `sessions_bad_ip_message`: message in case the user is not allowed to initiate a new session from its current IP address.
* `sessions_blocked_message`: message in case the user is not allowed to initiate a new session because the maximum number of active sessions has been reached for this user.

### Example
Change the message for bad IP:
```php
  add_filter(
    'sessions_bad_ip_message',
    function() {
      return 'You\'re not allowed to initiate a new session from your current IP address.';
    },
    10,
    0
  );
```

## Customization of PerfOps One menus
You can use the `poo_hide_main_menu` filter to completely hide the main PerfOps One menu or use the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters to selectively hide submenus.

### Example
Hide the main menu:
```php
  add_filter( 'poo_hide_main_menu', '__return_true' );
```

## Customization of the admin bar
You can use the `poo_hide_adminbar` filter to completely hide this plugin's item(s) from the admin bar.

### Example
Remove this plugin's item(s) from the admin bar:
```php
  add_filter( 'poo_hide_adminbar', '__return_true' );
```

## Advanced settings and controls
By default, advanced settings and controls are hidden to avoid cluttering admin screens. Nevertheless, if this plugin have such settings and controls, you can force them to display with `perfopsone_show_advanced` filter.

### Example
Display advanced settings and controls in admin screens:
```php
  add_filter( 'perfopsone_show_advanced', '__return_true' );
```