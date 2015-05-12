!function ($) {
        var standalone = navigator.standalone
            , UA = navigator.userAgent
            , type = null;

        if (UA.match(/iPhone|iPod/i) != null || (UA.match(/iPad/) && false)) {
            if (UA.match(/Safari/i) != null &&
                (UA.match(/CriOS/i) != null  || window.Number(UA.substr(UA.indexOf('OS ') + 3, 3).replace('_', '.')) < 10))
                type = 'ios'; // Check webview and native smart banner support (iOS 6+)
        } else if (UA.match(/\bSilk\/(.*\bMobile Safari\b)?/) || UA.match(/\bKF\w/) || UA.match('Kindle Fire')) {
            type = 'kindle'
        } else if (UA.match(/Android/i) != null) {
            type = 'android'
        }

        if (!type) {
            var ua = window.navigator ? window.navigator.userAgent : null;

            if (ua && ua.indexOf('ZFirefox') == -1 && ua.indexOf('Camino') == -1) {
                if (ua.indexOf('iPhone') != -1 || ua.indexOf('iPod') != -1) {
                    type = 'ios'
                } else if (ua.indexOf('iPad') != -1) {
//                    ipad = true;
                } else if (ua.indexOf('Chrome') == -1 && ua.indexOf('Android') != -1) {
                    type = 'android';
                }
            }
        }

        if (!type) {
            window.location.href = 'http://vmeste-app.ru';
            return;
        }

        var meta = $(type == 'android' ? 'meta[name="google-play-app"]' :
            type == 'ios' ? 'meta[name="apple-itunes-app"]' :
                type == 'kindle' ? 'meta[name="kindle-fire-app"]' : 'meta[name="msApplication-ID"]');
        if (meta.length == 0) {
            window.location.href = "http://vmeste-app.ru";
            return;
        }

//        For Windows Store apps, get the PackageFamilyName for protocol launch
        if (type == 'windows') {
            pfn = $('meta[name="msApplication-PackageFamilyName"]').attr('content');
            appId = meta.attr('content')[1]
        } else {
            appId = /app-id=([^\s,]+)/.exec(meta.attr('content'))[1]
        }

        // Create banner
//        var link=(false ? 'http://vmeste-app.ru' : (type == 'windows' ? 'ms-windows-store:PDP?PFN=' + pfn : (type == 'android' ? 'market://details?id=' : (type == 'kindle' ? 'amzn://apps/android?asin=' : 'https://itunes.apple.com/app/id'))) + appId)

            if(type=='android') {
                link = 'market://details?id=' + appId;
                yaCounter25326449.reachGoal('google_clik');
            }
            else
                link = 'https://itunes.apple.com/app/id' + appId;

        window.location.href = link;

}(window.jQuery);