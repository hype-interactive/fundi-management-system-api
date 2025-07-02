@extends('layouts.main')
@section('layout')
    <div class="main-wrapper">
        <div class="d-flex">
            <div class="navigation">
                <div class="brand-logo">
                    <img src="{{ asset('images/logo.png') }}" width="100" alt="">
                    @if (isRoot())
                        <h6> Super User</h6>
                    @else
                        <h6>Administrator</h6>
                    @endif
                </div>

                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="/"> <i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/fundis"> <i class="fa-solid fa-people-group"></i> Mafundi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/site-visits">
                            <i class="fa-solid fa-location"></i></i>
                            Site Visits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/financials">
                            <i class="fa-solid fa-coins"></i></i>
                            Financials
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/messaging">
                            <i class="fa-solid fa-message"></i></i>
                            Messaging
                        </a>
                    </li>
                    <div class="divider"></div>
                    <li class="nav-item management">
                        <a class="nav-link " data-toggle="collapse" href="#collapseExample" role="button"
                            aria-expanded="false" aria-controls="collapseExample"><i class="fa-solid fa-folder-tree"></i>
                            Management</a>
                        <div class="collapse mb-3" id="collapseExample">
                            <div class="card card-body">
                                @if (isRoot())
                                    <a href="/administrators" class="btn btn-custom inner-btn">
                                        Administrators
                                        <i class="fa-solid fa-user-gear"></i>
                                    </a>
                                @endif
                                <a href="/operators" class="btn btn-custom inner-btn mt-3">Operators <i
                                        class="fa-solid fa-user-group"></i></a>
                                <a href="/legacy-data" class="btn btn-custom inner-btn mt-3">Legacy Data <i
                                        class="fa-regular fa-folder-open"></i></a>
                                <a href="/products" class="btn btn-custom inner-btn mt-3">Products <i
                                        class="fa-solid fa-shopping-bag"></i></a>
                            </div>
                        </div>
                    </li>
                    @if (isRoot())
                        <li class="nav-item">
                            <a href="/logs" class="nav-link"><i class="fa-solid fa-file-shield"></i> Logs</a>
                        </li>
                    @endif
                </ul>
                <div class="avatar-wrapper dropup">
                    <a class="avatar" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="{{ asset('images/avatar.png') }}" alt="">
                    </a>

                    <div class="d-flex justify-content-start flex-column">
                        <div class="name">
                            Mr. {{ Auth::user()->fname }}
                        </div>
                        <div class="phone">
                            {{ Auth::user()->phone }}
                        </div>
                    </div>
                    <div class="dropdown-menu ml-1 mb-3">
                        <div class="logout">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a :href="route('logout')"
                                    onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-fluid p-3">
                <div class="main-body">
                    @yield('page')
                    {{ $slot ?? '' }}
                </div>
            </div>
        </div>
    </div>
@endsection
