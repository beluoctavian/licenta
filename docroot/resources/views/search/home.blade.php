<!DOCTYPE html>
<html>
    <head>
        <title>Search{{ !empty($_GET['q']) ? " for '{$_GET['q']}'" : '' }}</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
        <link rel="stylesheet" href="{{ URL::asset('assets/libraries/bootstrap/css/bootstrap.min.css') }}">
        <script src="{{ URL::asset('assets/libraries/jquery/jquery-1.12.4.min.js') }}"></script>
        <script src="{{ URL::asset('assets/libraries/bootstrap/js/bootstrap.min.js') }}"></script>
        <link rel="stylesheet" href="{{ URL::asset('assets/css/style.css') }}">
    </head>
    <body>
        <div id="visualization"></div>
        <div class="container">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <form id="search-form" method="get" action="/search">
                        <a class="btn btn-primary" href="{{ URL::to('/') }}">Home</a>
                        <input name="q" type="text" maxlength="2048" class="search-text" value="{{ !empty($_GET['q']) ? trim($_GET['q']) : '' }}">
                        <select name="n">
                            <option value="10" {{ !empty($_GET['n']) && $_GET['n'] == '10' ? 'selected' : '' }}>10</option>
                            <option value="20" {{ !empty($_GET['n']) && $_GET['n'] == '20' ? 'selected' : '' }}>20</option>
                            <option value="50" {{ !empty($_GET['n']) && $_GET['n'] == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ !empty($_GET['n']) && $_GET['n'] == '100' ? 'selected' : '' }}>100</option>
                        </select>
                        <select name="engine">
                            <option value="bing" {{ !empty($_GET['engine']) && $_GET['engine'] == 'bing' ? 'selected' : '' }}>Bing</option>
                            <option value="google" {{ !empty($_GET['engine']) && $_GET['engine'] == 'google' ? 'selected' : '' }}>Google</option>
                        </select>
                        <input type="checkbox" name="advanced" value="1" {{ !empty($_GET['advanced']) ? 'checked' : '' }}> <span>Advanced</span>
                        <button type="submit" class="search-form-submit btn btn-primary">
                            <span class="glyphicon glyphicon-search" aria-hidden="true"></span>
                        </button>
                    </form>
                    <div id="please_wait">Please wait...</div>
                </div>
            </div>
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
                    url: "{{ URL::to('/getSearchResults') }}" + window.location.search,
                    dataType: "json",
                    jsonpCallback: "callback",
                    success: function(data) {
                        $('#please_wait').hide();
                        var groups = data.map(createGroupNode);

                        foamtree.set({
                            dataObject: { groups: groups },
                            rolloutDuration: 3000
                        });
                    },
                    timeout: 30000
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
