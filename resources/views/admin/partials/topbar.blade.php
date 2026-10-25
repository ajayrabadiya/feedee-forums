            <header class="admin-top">
                <button class="side-toggle" type="button" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
                <div>
                    <h1>@yield('heading')</h1>
                    <p class="sub">@yield('subheading')</p>
                </div>
                <form class="admin-search" role="search" action="{{ route('admin.posts') }}">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" placeholder="Search posts, users or IDs" aria-label="Search">
                </form>
                <a href="{{ route('admin.reports') }}" class="top-icon" aria-label="5 open reports"><i class="bi bi-bell"></i><span class="dot"></span></a>
                <div class="dropdown">
                    <button class="admin-user" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://picsum.photos/seed/ff-admin/80/80" width="38" height="38" alt="Admin profile photo">
                        <span class="who"><strong>Alex Morgan</strong><span>Administrator</span></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @unless (request()->routeIs('admin.docs'))
                            <li><a class="dropdown-item" href="{{ route('admin.docs') }}"><i class="bi bi-book"></i>Admin guide</a></li>
                        @endunless
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-house-door"></i>View site</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="{{ route('login') }}"><i class="bi bi-box-arrow-left"></i>Log out</a></li>
                    </ul>
                </div>
            </header>
