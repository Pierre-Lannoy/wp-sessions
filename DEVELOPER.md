# Developing for Device Detector

Before starting to explain how to use Device Detector from a developer point of view, I would like to thank you to take the time to invest your knowledge and skills in making Device Detector better and more useful. I'll only have one word: you rock! (OK, that's two words)

Now, what's the menu today?

1. [What is Device Detector?](#what-is-sessions)
2. [Device Detector API](#sessions-api)
    - [Querying characteristics](#querying-characteristics)
    - [Getting medias](#getting-medias)
3. [Contribution Guidelines](/CONTRIBUTING.md)
4. [Code of Conduct](/CODE_OF_CONDUCT.md)

## What is Device Detector?

Device Detector is mainly a tool to analyze headers sent to a WordPress site. It has the same pros and cons as all other tools using HTTP headers: it is operational for every web server, there's no need of javascript from the client-side, but it is the responsability of the client to rightly send it (so it is "fakable" by the client).

__For this reason, Device Detector shall in no case be used to implement security mechanisms.__

Device Detector, once activated, systematically analyzes the user-agent fields of the header and makes available, via a simple API, the data from the performed detection. When you use this API, you don't have to worry about detection and cache management. You can call this API as many times as you want without any performance impact and you can do it as soon as the `init` hook is executed.

## Device Detector API

### Querying characteristics

To query characteristics, you have one class to know: `\POSessions\API\Device`. Just call the static method `get()` without parameter to analyze the current user-agent string or with a string as parameter to force the user-agent string. It will return an instance of an object having all the right properties filed.

```php
    // Get the brand of the current user-agent string.
    $brand = POSessions\API\Device::get()->brand_name;
    
    // Get the OS while specifying the user-agent string.
    $ua = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0';
    $os = POSessions\API\Device::get( $ua )->os_name;
    
    // Do successive calls like that (performed detection is cached).
    $brand = POSessions\API\Device::get()->brand_name;
    $os    = POSessions\API\Device::get()->os_name;

```

The following properties are available:

```php
    /**
     * @var boolean  True if it's a bot, false otherwise.
     * @since   1.0.0
     */
    public $class_is_bot = false;

    /**
     * @var boolean  True if it's a desktop, false otherwise.
     * @since   1.0.0
     */
    public $class_is_desktop = false;

    /**
     * @var boolean  True if it's a mobile, false otherwise.
     * @since   1.0.0
     */
    public $class_is_mobile = false;

    /**
     * @var string  The name of the class translated if translation exists, else in english.
     * @since   1.0.0
     */
    public $class_full_type = '';

    /**
     * @var boolean  True if it's a smartphone, false otherwise.
     * @since   1.0.0
     */
    public $device_is_smartphone = false;

    /**
     * @var boolean  True if it's a featurephone, false otherwise.
     * @since   1.0.0
     */
    public $device_is_featurephone = false;

    /**
     * @var boolean  True if it's a tablet, false otherwise.
     * @since   1.0.0
     */
    public $device_is_tablet = false;

    /**
     * @var boolean  True if it's a phablet, false otherwise.
     * @since   1.0.0
     */
    public $device_is_phablet = false;

    /**
     * @var boolean  True if it's a console, false otherwise.
     * @since   1.0.0
     */
    public $device_is_console = false;

    /**
     * @var boolean  True if it's a portable media player, false otherwise.
     * @since   1.0.0
     */
    public $device_is_portable_media_player = false;

    /**
     * @var boolean  True if it's a car browser, false otherwise.
     * @since   1.0.0
     */
    public $device_is_car_browser = false;

    /**
     * @var boolean  True if it's a tv, false otherwise.
     * @since   1.0.0
     */
    public $device_is_tv = false;

    /**
     * @var boolean  True if it's a smart display, false otherwise.
     * @since   1.0.0
     */
    public $device_is_smart_display = false;

    /**
     * @var boolean  True if it's a camera, false otherwise.
     * @since   1.0.0
     */
    public $device_is_camera = false;

    /**
     * @var string  The name of the device type translated if translation exists, else in english.
     * @since   1.0.0
     */
    public $device_full_type = '';

    /**
     * @var boolean  True if it's a browser, false otherwise.
     * @since   1.0.0
     */
    public $client_is_browser = false;

    /**
     * @var boolean  True if it's a feed reader, false otherwise.
     * @since   1.0.0
     */
    public $client_is_feed_reader = false;

    /**
     * @var boolean  True if it's a mobile app, false otherwise.
     * @since   1.0.0
     */
    public $client_is_mobile_app = false;

    /**
     * @var boolean  True if it's a PIM, false otherwise.
     * @since   1.0.0
     */
    public $client_is_pim = false;

    /**
     * @var boolean  True if it's a library, false otherwise.
     * @since   1.0.0
     */
    public $client_is_library = false;

    /**
     * @var boolean  True if it's a media player, false otherwise.
     * @since   1.0.0
     */
    public $client_is_media_player = false;

    /**
     * @var string  The name of the client type translated if translation exists, else in english.
     * @since   1.0.0
     */
    public $client_full_type = '';

    /**
     * @var boolean  True if device has touch enabled, false otherwise.
     * @since   1.0.0
     */
    public $has_touch_enabled = false;

    /**
     * @var string  The OS name.
     * @since   1.0.0
     */
    public $os_name = '';

    /**
     * @var string  The OS short name.
     * @since   1.0.0
     */
    public $os_short_name = '';

    /**
     * @var string  The OS version.
     * @since   1.0.0
     */
    public $os_version = '';

    /**
     * @var string  The OS platform.
     * @since   1.0.0
     */
    public $os_platform = '';

    /**
     * @var string  The client type.
     * @since   1.0.0
     */
    public $client_type = '';

    /**
     * @var string  The client name.
     * @since   1.0.0
     */
    public $client_name = '';

    /**
     * @var string  The client short name.
     * @since   1.0.0
     */
    public $client_short_name = '';

    /**
     * @var string  The client version.
     * @since   1.0.0
     */
    public $client_version = '';

    /**
     * @var string  The client engine.
     * @since   1.0.0
     */
    public $client_engine = '';

    /**
     * @var string  The client engine version.
     * @since   1.0.0
     */
    public $client_engine_version = '';

    /**
     * @var string  The brand name.
     * @since   1.0.0
     */
    public $brand_name = '';

    /**
     * @var string  The brand short name.
     * @since   1.0.0
     */
    public $brand_short_name = '';

    /**
     * @var string  The model name.
     * @since   1.0.0
     */
    public $model_name = '';

    /**
     * @var string  The bot name.
     * @since   1.0.0
     */
    public $bot_name = '';

    /**
     * @var string  The bot category.
     * @since   1.0.0
     */
    public $bot_category = '';

	/**
	 * @var string  The bot category translated if translation exists, else in english.
	 * @since   1.0.0
	 */
	public $bot_full_category = '';

    /**
     * @var string  The bot url.
     * @since   1.0.0
     */
    public $bot_url = '';

    /**
     * @var string  The bot producer name.
     * @since   1.0.0
     */
    public $bot_producer_name = '';

    /**
     * @var string  The bot producer url.
     * @since   1.0.0
     */
     public $bot_producer_url = '';  

```

### Getting medias

To query corresponding medias (icons for brand, OS, browser and bot), you can use the same class (`\POSessions\API\Device`) like this:

```php
    // Display the brand name with its icon.
    echo POSessions\API\Device::get()->brand_icon_image();
    echo '&nbsp;&nbsp;';
    echo POSessions\API\Device::get()->brand_name;

```

The following methods are available:

```php
    /**
     * Get the brand icon base64 encoded.
     *
     * @return string  The icon base64 encoded.
     * @since    1.0.0
     */
    public function brand_icon_base64() {...}

    /**
     * Get the brand icon html image tag.
     *
     * @return string  The icon, as html image, ready to print.
     * @since    1.0.0
     */
    public function brand_icon_image() {...}

    /**
     * Get the os icon base64 encoded.
     *
     * @return string  The icon base64 encoded.
     * @since    1.0.0
     */
    public function os_icon_base64() {...}

    /**
     * Get the os icon html image tag.
     *
     * @return string  The icon, as html image, ready to print.
     * @since    1.0.0
     */
    public function os_icon_image() {...}

    /**
     * Get the browser icon base64 encoded.
     *
     * @return string  The icon base64 encoded.
     * @since    1.0.0
     */
    public function browser_icon_base64() {...}

    /**
     * Get the browser icon html image tag.
     *
     * @return string  The icon, as html image, ready to print.
     * @since    1.0.0
     */
    public function browser_icon_image() {...}

    /**
     * Get the bot icon base64 encoded.
     *
     * @return string  The icon base64 encoded.
     * @since    1.0.0
     */
    public function bot_icon_base64() {...}

    /**
     * Get the bot icon html image tag.
     *
     * @return string  The icon, as html image, ready to print.
     * @since    1.0.0
     */
    public function bot_icon_image() {...}

```

> If you think this documentation is incomplete, not clear, etc. Do not hesitate to open an issue and/or make a pull request.
