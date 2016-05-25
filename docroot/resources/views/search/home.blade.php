<!DOCTYPE html>
<html>
    <head>
        <title>Search</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
    </head>
    <body>
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
                @endif
            </div>
        </div>
    </body>
</html>
