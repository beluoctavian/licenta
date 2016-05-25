<!DOCTYPE html>
<html>
    <head>
        <title>Search{{ !empty($_GET['q']) ? " for '{$_GET['q']}'" : '' }}</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
        <script src="{{ URL::asset('assets/libraries/jquery/jquery-1.12.4.min.js') }}"></script>
    </head>
    <body>
        <div id="visualization" style="width: 100%; height: 600px"></div>
        <div class="container">
            @if (!empty($error))
                <div class="error">{{ $error }}</div>
            @endif
            <div class="content">
                <form id="search-form" method="get" action="/search">
                    <input name="q" type="text" maxlength="2048" class="search-text" value="{{ !empty($_GET['q']) ? $_GET['q'] : '' }}">
                    <button type="submit" class="search-form-submit">
                        <span class="fa fa-search" aria-hidden="true"></span>
                    </button>
                </form>
                @if (!empty($results))
                    <div class="results">
                        @foreach($results as $result)
                            <div class="item">
                                <h3><a href="{{ $result['url'] }}">{{ $result['title'] }}</a></h3>
                            </div>
                        @endforeach
                    </div>
                    <div class="paginator">
                        {!! $results->render() !!}
                    </div>
                @endif
            </div>
        </div>
        <script src="{{ URL::asset('assets/libraries/foamtree-3.4.2/carrotsearch.foamtree.js') }}"></script>
        <script>
            var foamtree = new CarrotSearchFoamTree({
                id: "visualization"
            });
            foamtree.set({
                dataObject: { groups: [ { label: "Please wait..." } ] },
                fadeDuration: 500,
                rectangleAspectRatioPreference: 0,
                stacking: 'flattened'
            });
            window.addEventListener("load", function() {
                $.ajax({
                    url: "{{ URL::to('/getSearchResults?q=' . (!empty($_GET['q']) ? $_GET['q'] : '')) }}",
                    dataType: "json",
                    jsonpCallback: "callback",
                    success: function(data) {
                        var states = data.response;

                        var groups = data.map(function(obj) {
                            console.log(obj);
                            return {
                                label: obj.title,
                                weight: 1
                            };
                        });

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
