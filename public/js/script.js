$(function(){
    function setItemHeight(){
        var arr = [];
        for (var i = 0; i < $('.item-desc').length; i++) {
            arr.push( $('.item-desc').eq(i).height() )
        };
        var maxHeight = Math.max(arr);
        $('.item-desc').css('minHeight',maxHeight);
    }
    setItemHeight(); $(window).resize(setItemHeight);
    $.scrollIt({
        onPageChange: function(pageIndex){
            $('#sub-phone').attr({
                class: 'sub-phone'+pageIndex
            });
        }
    });


    $(".market").on('click', function(e) {
        e.preventDefault();
        this.origHtmlMargin = parseFloat($('html').css('margin-top')) // Get the original margin-top of the HTML element so we can take that into account
        this.options = {
            title: null, // What the title of the app should be in the banner (defaults to <title>)
            author: null, // What the author of the app should be in the banner (defaults to <meta name="author"> or hostname)
            price: 'FREE', // Price of the app
            appStoreLanguage: 'us', // Language code for App Store
            inAppStore: 'On the App Store', // Text of price for iOS
            inGooglePlay: 'In Google Play', // Text of price for Android
            inAmazonAppStore: 'In the Amazon Appstore',
            inWindowsStore: 'In the Windows Store', //Text of price for Windows
            GooglePlayParams: null, // Aditional parameters for the market
            icon: null, // The URL of the icon (defaults to <meta name="apple-touch-icon">)
            iconGloss: null, // Force gloss effect for iOS even for precomposed
            button: 'VIEW', // Text for the install button
            url: null, // The URL for the button. Keep null if you want the button to link to the app store.
            scale: 'auto', // Scale based on viewport size (set to 1 to disable)
            speedIn: 300, // Show animation speed of the banner
            speedOut: 400, // Close animation speed of the banner
            daysHidden: 15, // Duration to hide the banner after being closed (0 = always show banner)
            daysReminder: 90, // Duration to hide the banner after "VIEW" is clicked *separate from when the close button is clicked* (0 = always show banner)
            force: null, // Choose 'ios', 'android' or 'windows'. Don't do a browser check, just always show this banner
            hideOnInstall: true, // Hide the banner after "VIEW" is clicked.
            layer: false, // Display as overlay layer or slide down the page
            iOSUniversalApp: true, // If the iOS App is a universal app for both iPad and iPhone, display Smart Banner to iPad users, too.
            appendToSelector: 'body' //Append the banner to a specific selector
        }

        var standalone = navigator.standalone // Check if it's already a standalone web app or running within a webui view of an app (not mobile safari)
            , UA = navigator.userAgent

        // Detect banner type (iOS or Android)
        if (this.options.force) {
            this.type = this.options.force
        } else if (UA.match(/iPhone|iPod/i) != null || (UA.match(/iPad/) && this.options.iOSUniversalApp)) {
            if (UA.match(/Safari/i) != null &&
                (UA.match(/CriOS/i) != null  || window.Number(UA.substr(UA.indexOf('OS ') + 3, 3).replace('_', '.')) < 10))
                this.type = 'ios' // Check webview and native smart banner support (iOS 6+)
        } else if (UA.match(/\bSilk\/(.*\bMobile Safari\b)?/) || UA.match(/\bKF\w/) || UA.match('Kindle Fire')) {
            this.type = 'kindle'
        } else if (UA.match(/Android/i) != null) {
            this.type = 'android'
        }

        if (!this.type) {
            var ua = window.navigator ? window.navigator.userAgent : null;

            if (ua && ua.indexOf('Firefox') == -1 && ua.indexOf('Camino') == -1) {
                if (ua.indexOf('iPhone') != -1 || ua.indexOf('iPod') != -1) {
                    this.type = 'ios'
                } else if (ua.indexOf('iPad') != -1) {
//                    ipad = true;
                } else if (ua.indexOf('Chrome') == -1 && ua.indexOf('Android') != -1) {
                    this.type = 'android';
                } else {
                    this.type = 'ios';
                }
            }
        }

        if (!this.type) {

            return false;
        }


        // Calculate scale
        this.scale = this.options.scale == 'auto' ? $(window).width() / window.screen.width : this.options.scale
        if (this.scale < 1) this.scale = 1

        // Get info from meta data
        var meta = $(this.type == 'android' ? 'meta[name="google-play-app"]' :
            this.type == 'ios' ? 'meta[name="apple-itunes-app"]' :
                this.type == 'kindle' ? 'meta[name="kindle-fire-app"]' : 'meta[name="msApplication-ID"]');
        if (meta.length == 0) {

            return false;
        }

        // For Windows Store apps, get the PackageFamilyName for protocol launch
        if (this.type == 'windows') {
            this.pfn = $('meta[name="msApplication-PackageFamilyName"]').attr('content');
            this.appId = meta.attr('content')[1]
        } else {
            this.appId = /app-id=([^\s,]+)/.exec(meta.attr('content'))[1]
        }

        this.title = this.options.title ? this.options.title : meta.data('title') || $('title').text().replace(/\s*[|\-·].*$/, '')
        this.author = this.options.author ? this.options.author : meta.data('author') || ($('meta[name="author"]').length ? $('meta[name="author"]').attr('content') : window.location.hostname)
        this.iconUrl = meta.data('icon-url');
        this.price = meta.data('price');

        var iconURL
            , link=(this.options.url ? this.options.url : (this.type == 'windows' ? 'ms-windows-store:PDP?PFN=' + this.pfn : (this.type == 'android' ? 'market://details?id=' : (this.type == 'kindle' ? 'amzn://apps/android?asin=' : 'https://itunes.apple.com/' + this.options.appStoreLanguage + '/app/id'))) + this.appId)
            , price = this.price || this.options.price
            , inStore=price ? price + ' - ' + (this.type == 'android' ? this.options.inGooglePlay : this.type == 'kindle' ? this.options.inAmazonAppStore : this.type == 'ios' ? this.options.inAppStore : this.options.inWindowsStore) : ''
            , gloss=this.options.iconGloss === null ? (this.type=='ios') : this.options.iconGloss
        if(this.options.url)
            link = this.options.url
        else {
            if(this.type=='android') {
                link = 'market://details?id=' + this.appId
                if(this.options.GooglePlayParams)
                    link = link + '&referrer=' + this.options.GooglePlayParams
            }
            else
                link = 'https://itunes.apple.com/' + this.options.appStoreLanguage + '/app/id' + this.appId
        }

        window.location.href = link;

    });



});