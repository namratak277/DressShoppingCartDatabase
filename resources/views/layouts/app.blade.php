<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dress Shop')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/home.css">
</head>
<body>

<header class="navigation">
    <div class="nav-inner">
        <a href="/" class="nav-brand">Dress Shop</a>
        <div class="nav-search" id="nav-search">
            <input type="text" id="search-input" placeholder="Search dresses by name, color, size..." autocomplete="off">
            <div class="search-results" id="search-results"></div>
        </div>
        <nav class="nav-links">
            <a href="/">Home</a>
            <a href="/cart">
                Cart
                <span class="cart-badge" id="cart-badge" style="display:{{ session('cart') && count(session('cart')) > 0 ? 'inline-flex' : 'none' }}">
                    {{ session('cart') ? array_sum(array_column(session('cart'), 'quantity')) : 0 }}
                </span>
            </a>
            <a href="/admin/login.php">Admin</a>
        </nav>
        <button class="nav-toggle" id="nav-toggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<main class="main-content">
    @if(session('success'))
        <div class="alert alert-success animate-slide-down">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error animate-slide-down">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <h3>Dress Shop</h3>
            <p>Your destination for beautiful dresses.</p>
        </div>
        <div class="footer-links">
            <h4>Quick Links</h4>
            <a href="/">Browse Dresses</a>
            <a href="/cart">Shopping Cart</a>
        </div>
        <div class="footer-contact">
            <h4>Contact</h4>
            <p>support&#64;dressshop.com</p>
            <p>&copy; {{ date('Y') }} Dress Shop</p>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('nav-toggle');
    const links = document.querySelector('.nav-links');
    const search = document.getElementById('nav-search');
    if (toggle) {
        toggle.addEventListener('click', function() {
            links.classList.toggle('open');
            search.classList.toggle('open');
            toggle.classList.toggle('active');
        });
    }

    // Search functionality
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    let debounceTimer;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            if (query.length < 2) {
                searchResults.innerHTML = '';
                searchResults.classList.remove('visible');
                return;
            }
            debounceTimer = setTimeout(function() {
                fetch('/?search=' + encodeURIComponent(query), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(function(r) { return r.json(); })
                .then(function(dresses) {
                    if (dresses.length === 0) {
                        searchResults.innerHTML = '<div class="search-no-results">No dresses found</div>';
                    } else {
                        searchResults.innerHTML = dresses.map(function(d) {
                            var img = d.image ? '/images/' + encodeURIComponent(d.image) : '';
                            return '<a href="/product/' + d.id + '" class="search-result-item">' +
                                (img ? '<img src="' + img + '" alt="">' : '<div class="search-no-img">No image</div>') +
                                '<div class="search-result-info">' +
                                    '<span class="search-result-name">' + escapeHtml(d.name) + '</span>' +
                                    '<span class="search-result-price">$' + Number(d.price).toFixed(2) + '</span>' +
                                '</div>' +
                            '</a>';
                        }).join('');
                    }
                    searchResults.classList.add('visible');
                })
                .catch(function() {
                    searchResults.innerHTML = '';
                    searchResults.classList.remove('visible');
                });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('visible');
            }
        });

        searchInput.addEventListener('focus', function() {
            if (searchResults.innerHTML) searchResults.classList.add('visible');
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Animate items on scroll
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.item, .product-detail').forEach(function(el) {
        observer.observe(el);
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(function() { alert.remove(); }, 400);
        }, 4000);
    });
});
</script>
</body>
</html>
