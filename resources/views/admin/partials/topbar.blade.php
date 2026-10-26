            <header class="admin-top">
                <button class="side-toggle" type="button" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
                <div>
                    <h1>@yield('heading')</h1>
                    <p class="sub">@yield('subheading')</p>
                </div>
                <div class="dropdown">
                    <button class="admin-user" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://picsum.photos/seed/ff-admin/80/80" width="38" height="38" alt="Admin profile photo">
                        <span class="who"><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->role->label() }}</span></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-house-door"></i>View site</a></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-left"></i>Log out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </header>
