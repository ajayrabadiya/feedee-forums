@extends('layouts.admin')

@section('title', 'Support link analytics - Feedee Forums Admin')
@section('heading', 'Support link analytics')
@section('subheading', 'Clicks on the YouPay and Cash App links, and where visitors click from')

@section('content')
    @php
        $range = $report['range'];
        $isCustom = $range['key'] === 'custom';
    @endphp
                <div class="support-links" data-support-links data-url="{{ route('admin.support-links.data') }}" data-activity-url="{{ route('admin.support-links.activity') }}" data-export-url="{{ route('admin.support-links.export') }}">
                    <div class="clicks-notice mb-4" role="note">
                        <span class="stat-icon ic-amber"><i class="bi bi-info-lg"></i></span>
                        <p><strong>Clicks, not payments.</strong> These figures count clicks on the Support button's payment links. A click does not mean that a payment or donation was made.</p>
                    </div>

                    @unless ($geoipInstalled || filled($geoipHeader))
                        <div class="clicks-notice is-warning mb-4" role="note">
                            <span class="stat-icon ic-red"><i class="bi bi-globe2"></i></span>
                            <p><strong>Country detection is not set up yet.</strong> New clicks are listed under "Unknown" until the free country database is imported. Run <code>php artisan app:import-geoip</code> on the server (it then updates itself monthly through the scheduler).</p>
                        </div>
                    @endunless

                    <!-- Filters -->
                    <section class="panel mb-4">
                        <div class="panel-head">
                            <div>
                                <h2>Reporting period</h2>
                                <p data-links-summary>{{ $range['label'] }} &middot; {{ $range['from'] }} – {{ $range['to'] }} &middot; times in {{ $range['timezone'] }}</p>
                            </div>
                            <div class="dropdown">
                                <button type="button" class="btn-soft sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-download"></i>Export CSV</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" data-export="countries" href="{{ route('admin.support-links.export', ['report' => 'countries', 'range' => $range['key']]) }}"><i class="bi bi-globe2"></i>Country report</a></li>
                                    <li><a class="dropdown-item" data-export="daily" href="{{ route('admin.support-links.export', ['report' => 'daily', 'range' => $range['key']]) }}"><i class="bi bi-calendar3"></i>Daily statistics</a></li>
                                    <li><a class="dropdown-item" data-export="clicks" href="{{ route('admin.support-links.export', ['report' => 'clicks', 'range' => $range['key']]) }}"><i class="bi bi-list-ul"></i>Every click</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="tabs-line" role="group" aria-label="Reporting period">
                            @foreach ($ranges as $option)
                                <button type="button" data-range="{{ $option->value }}" @class(['active' => $option->value === $range['key']]) aria-pressed="{{ $option->value === $range['key'] ? 'true' : 'false' }}">{{ $option->label() }}</button>
                            @endforeach
                        </div>
                        <form class="toolbar support-range border-0" data-links-custom @unless ($isCustom) hidden @endunless novalidate>
                            <label class="range-field">From <input type="date" class="form-control" name="from" value="{{ $isCustom ? $range['from'] : now()->subDays(29)->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
                            <label class="range-field">To <input type="date" class="form-control" name="to" value="{{ $isCustom ? $range['to'] : now()->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
                            <button type="submit" class="btn-fill sm"><i class="bi bi-funnel"></i>Apply</button>
                            <span class="range-hint">Up to {{ \App\Models\SupportClick::MAX_RANGE_DAYS }} days</span>
                        </form>
                    </section>

                    <!-- Selected period -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card">
                                <div class="stat-top"><span class="stat-icon ic-orange"><i class="bi bi-cursor-fill"></i></span><span class="trend flat" data-delta="clicks"></span></div>
                                <strong data-stat="clicks">{{ number_format($report['totals']['clicks']) }}</strong>
                                <span class="label">Total support clicks</span>
                            </div>
                        </div>
                        @foreach ($report['platforms'] as $platform)
                            <div class="col-sm-6 col-xl-3">
                                <div class="stat-card">
                                    <div class="stat-top"><span class="stat-icon" style="--pf: {{ $platform['color'] }}"><i class="bi {{ $platform['icon'] }}"></i></span><span class="trend flat" data-platform-delta="{{ $platform['key'] }}"></span></div>
                                    <strong data-platform-clicks="{{ $platform['key'] }}">{{ number_format($platform['clicks']) }}</strong>
                                    <span class="label">{{ $platform['label'] }} clicks <span class="card-note" data-platform-share="{{ $platform['key'] }}">&middot; {{ $platform['share'] }}%</span></span>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card" title="Approximate: members count once per account, guests once per network and browser.">
                                <div class="stat-top"><span class="stat-icon ic-blue"><i class="bi bi-people"></i></span><span class="trend flat" data-delta="visitors"></span></div>
                                <strong data-stat="visitors">{{ number_format($report['totals']['visitors']) }}</strong>
                                <span class="label">Unique visitors <span class="card-note">&middot; approximate</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed periods -->
                    <div class="row g-3 mb-4">
                        @foreach ($report['periods'] as $key => $period)
                            <div class="col-sm-6 col-xl-3">
                                <div class="stat-card period-card" data-period="{{ $key }}">
                                    <div class="stat-top"><span class="label">{{ $period['label'] }}</span><small class="muted" data-period-since>{{ $period['since'] }}</small></div>
                                    <strong data-period-total>{{ number_format($period['total']) }}</strong>
                                    <ul class="platform-lines">
                                        @foreach ($report['platforms'] as $platform)
                                            <li><span class="pf-dot" style="--pf: {{ $platform['color'] }}"></span>{{ $platform['label'] }}<b data-period-platform="{{ $platform['key'] }}">{{ number_format($period['platforms'][$platform['key']]) }}</b></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card period-card">
                                <div class="stat-top"><span class="label">Not counted</span><small class="muted">selected period</small></div>
                                <strong data-filtered-total>{{ number_format($report['filtered']['total']) }}</strong>
                                <ul class="platform-lines">
                                    @foreach ($report['filtered']['reasons'] as $reason)
                                        <li><span class="pf-dot is-muted"></span>{{ $reason['label'] }}<b data-filtered-reason="{{ $reason['key'] }}">{{ number_format($reason['clicks']) }}</b></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <!-- Activity chart -->
                        <div class="col-xl-8">
                            <section class="panel h-100">
                                <div class="panel-head">
                                    <div>
                                        <h2>Click activity</h2>
                                        <p data-activity-summary>Clicks per platform over the selected period</p>
                                    </div>
                                    <div class="view-toggle" role="group" aria-label="Group chart by">
                                        @foreach (['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly'] as $group => $label)
                                            <button type="button" data-group="{{ $group }}" @class(['active' => $report['group'] === $group]) aria-pressed="{{ $report['group'] === $group ? 'true' : 'false' }}">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="panel-body fill">
                                    <div class="legend mb-2">
                                        @foreach ($report['platforms'] as $platform)
                                            <span><i style="background: {{ $platform['color'] }}"></i>{{ $platform['label'] }}</span>
                                        @endforeach
                                    </div>
                                    <div class="bar-chart stacked-chart" role="img" aria-label="Stacked bar chart of support link clicks" data-links-chart></div>
                                    <p class="chart-empty" data-links-chart-empty hidden>No support link clicks in this period.</p>
                                </div>
                            </section>
                        </div>

                        <!-- Platform comparison -->
                        <div class="col-xl-4">
                            <section class="panel h-100">
                                <div class="panel-head">
                                    <div>
                                        <h2>Platform comparison</h2>
                                        <p>Share of clicks in the selected period</p>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <div class="donut" data-links-donut role="img" aria-label="Platform share of clicks"><span><strong data-donut-total>0</strong><small>clicks</small></span></div>
                                    <ul class="platform-compare" data-links-compare></ul>
                                </div>
                            </section>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <!-- Top countries chart -->
                        <div class="col-xl-5">
                            <section class="panel h-100">
                                <div class="panel-head">
                                    <div>
                                        <h2>Top countries</h2>
                                        <p>Which platform visitors from each country choose</p>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <div class="country-bars" data-links-country-chart></div>
                                    <p class="chart-empty" data-links-country-empty hidden>No clicks in this period.</p>
                                </div>
                            </section>
                        </div>

                        <!-- Country report -->
                        <div class="col-xl-7">
                            <section class="panel h-100">
                                <div class="panel-head">
                                    <div>
                                        <h2>Country report</h2>
                                        <p data-country-summary>Clicks per country in the selected period</p>
                                    </div>
                                    <div class="admin-search">
                                        <i class="bi bi-search"></i>
                                        <input type="search" placeholder="Filter countries" aria-label="Filter countries" data-country-search>
                                    </div>
                                </div>
                                <div class="table-wrap">
                                    <table class="admin-table" data-links-countries>
                                        <thead>
                                            <tr>
                                                <th scope="col">Country</th>
                                                @foreach ($report['platforms'] as $platform)
                                                    <th scope="col">{{ $platform['label'] }}</th>
                                                @endforeach
                                                <th scope="col">Total</th>
                                                <th scope="col">Share</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    </div>

                    <!-- Recent activity -->
                    <section class="panel mb-4">
                        <div class="panel-head">
                            <div>
                                <h2>Recent click activity</h2>
                                <p>Newest first, including clicks that were not counted. Visitor IDs are anonymous.</p>
                            </div>
                        </div>
                        <div class="table-wrap">
                            <table class="admin-table activity-table" data-links-activity>
                                <thead>
                                    <tr>
                                        <th scope="col">Date &amp; time</th>
                                        <th scope="col">Country</th>
                                        <th scope="col">Platform</th>
                                        <th scope="col">Clicked from</th>
                                        <th scope="col">Visitor</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="6" class="dt-empty">Loading&hellip;</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="panel-foot">
                            <span class="dt-info" data-activity-info></span>
                            <nav class="dt-paging" aria-label="Recent activity pages">
                                <ul class="pagination">
                                    <li class="page-item"><button type="button" class="page-link" data-activity-page="prev" aria-label="Previous page"><i class="bi bi-chevron-left"></i></button></li>
                                    <li class="page-item disabled"><span class="page-link" data-activity-page-label>1 / 1</span></li>
                                    <li class="page-item"><button type="button" class="page-link" data-activity-page="next" aria-label="Next page"><i class="bi bi-chevron-right"></i></button></li>
                                </ul>
                            </nav>
                        </div>
                    </section>

                    <!-- How it is counted -->
                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <h2>How these numbers are counted</h2>
                                <p>So the figures can be read with the right expectations</p>
                            </div>
                        </div>
                        <div class="panel-body">
                            <ul class="count-notes">
                                <li><i class="bi bi-cursor"></i><span>One click on YouPay or Cash App in the Support button is one click. Clicks by admins are never recorded.</span></li>
                                <li><i class="bi bi-arrow-repeat"></i><span>A second click on the same link by the same visitor within {{ $duplicateWindow }} seconds is kept but marked "Repeat within seconds", so double-clicks count once. Later repeat clicks count again.</span></li>
                                <li><i class="bi bi-robot"></i><span>Clicks from bots, link previews and scripts are marked "Bot or automated" and left out. Page prefetches are not recorded.</span></li>
                                <li><i class="bi bi-people"></i><span>Unique visitors are approximate: members count once per account, guests once per network and browser.</span></li>
                                <li><i class="bi bi-globe2"></i><span>Countries come from the visitor's IP address and are approximate. VPN and proxy users appear under the country of their VPN server. Clicks that cannot be located are shown as "Unknown".</span></li>
                                <li><i class="bi bi-shield-lock"></i><span>Full IP addresses are never stored, only the country and an anonymous visitor ID. @if ($retentionDays > 0) Clicks are deleted after {{ number_format($retentionDays) }} days. @else Clicks are kept until deleted manually. @endif</span></li>
                                <li><i class="bi bi-clock"></i><span>Dates and times use the site's timezone ({{ $range['timezone'] }}).</span></li>
                            </ul>
                            <p class="geo-credit mb-0">IP geolocation by <a href="https://db-ip.com" target="_blank" rel="noopener">DB-IP</a>, licensed under <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener">CC BY 4.0</a>.</p>
                        </div>
                    </section>

                    <script type="application/json" data-links-initial>@json($report)</script>
                </div>
@endsection
