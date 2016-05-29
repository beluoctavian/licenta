<!DOCTYPE html>
<html>
    <head>
        <title>Search{{ !empty($_GET['q']) ? " for '{$_GET['q']}'" : '' }}</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
        <link rel="stylesheet" href="{{ URL::asset('assets/css/style.css') }}">
        <script src="{{ URL::asset('assets/libraries/jquery/jquery-1.12.4.min.js') }}"></script>
    </head>
    <body>
        <div id="visualization"></div>
        <div class="container">
            <form id="search-form" method="get" action="/search">
                <input name="q" type="text" maxlength="2048" class="search-text" value="{{ !empty($_GET['q']) ? $_GET['q'] : '' }}">
                <select name="n">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <select name="engine">
                    <option value="bing">Bing</option>
                    <option value="google">Google</option>
                </select>
                <input type="checkbox" name="advanced" value="1" {{ !empty($_GET['advanced']) ? 'checked' : '' }}> <span>Advanced</span>
                <button type="submit" class="search-form-submit">
                    <span class="fa fa-search" aria-hidden="true"></span>
                </button>
            </form>
            <div id="please_wait">Please wait...</div>
        </div>
        <script src="{{ URL::asset('assets/libraries/foamtree-3.4.2/carrotsearch.foamtree.js') }}"></script>
        <script>
            var createGroupNode = function(obj) {
                if ('url' in obj) {
                    return {
                        label: obj.title,
                        url: obj.url,
                        displayUrl: obj.displayUrl,
                        weight: 'weight' in obj ? obj.weight : 1
                    };
                }
                else {
                    var groups = obj.children.map(createGroupNode);
                    return {
                        label: obj.title,
                        groups: groups,
                        weight: 'weight' in obj ? obj.weight : 1
                    };
                }
            };
            var foamtree = new CarrotSearchFoamTree({
                id: "visualization",
                onGroupClick: function (event) {
                    if ('url' in event.group) {
                        event.preventDefault();
                        window.location = event.group.url;
                    }
                },
                fadeDuration: 500
            });
            window.addEventListener("load", function() {
                $.ajax({
                    url: "{{ URL::to('/getSearchResults?q=' . (!empty($_GET['q']) ? $_GET['q'] : '')) }}",
                    dataType: "json",
                    jsonpCallback: "callback",
                    success: function(data) {
                        $('#please_wait').hide();
                        var groups = data.map(createGroupNode);

                        foamtree.set({
                            dataObject: { groups: groups },
                            rolloutDuration: 3000
                        });
                    }
                });
            });
            window.addEventListener("resize", (function() {
                var timeout;
                return function() {
                    window.clearTimeout(timeout);
                    timeout = window.setTimeout(foamtree.resize, 300);
                };
            })());
            window.addEventListener("orientationchange", function() {
                foamtree.resize();
            });
        </script>
    </body>
</html>
