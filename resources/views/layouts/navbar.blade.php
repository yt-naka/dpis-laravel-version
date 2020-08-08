<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- bootstrap 4 -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <!-- chart js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script> 
    <script type="text/javascript" src="https://github.com/nagix/chartjs-plugin-colorschemes/releases/download/v0.2.0/chartjs-plugin-colorschemes.min.js"></script>

	<title>PIS</title>
</head>
<body>
    <nav class="navbar navbar-expand-sm navbar-light bg-light fixed-top" id="header">
        <a class="navbar-brand" href="{{ url('/products') }}">TOP</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarsExample01" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li><a class="nav-item nav-link" href="{{ url('/products') }}">全ミッション</a></li>
                <li><a class="nav-item nav-link" href="{{ url('/products?mission=H') }}">Hミッション</a></li>
                <li><a class="nav-item nav-link" href="{{ url('/products?mission=HG') }}">HGミッション</a></li>
                <li><a class="nav-item nav-link" href="{{ url('/products?mission=HJ') }}">HJミッション</a></li>
                <li><a class="nav-item nav-link" href="{{ url('/products?mission=HGD') }}">HGDミッション</a></li>
                <li><a class="nav-item nav-link" href="{{ url('/products?mission=SH') }}">SHミッション</a></li>
                <li><a class="nav-item nav-link" href="{{ url('/products?mission=UM') }}">UMミッション</a></li>
                <li><a class="nav-item nav-link" href="{{ url('/products?mission=BM') }}">BMミッション</a></li>
                <li><a class="nav-item nav-link" href="{{ url('/changerate') }}">変化率</a></li>
                <form action="{{ url('/search') }}" class="form-inline my-2 my-lg-0">
                    <input name="keyword" class="form-control mr-sm-2">
                    <button type="submit" class="btn btn-outline-success my-2 my-sm-0">検索</button>
                </form>
            </ul>
        </div>
    </nav>
    <main class="py-4" id="main">
        @yield('content')
    </main>

    <script>
        const main = document.getElementById('main');
        const header_height = document.getElementById('header').clientHeight;
        main.style.position = 'relative';
        main.style.top  = `${header_height}px`;
    </script>
</body>
</html>
