<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'THERMO PREDICT')</title>

    <link rel="stylesheet" href="/assets/css/style.css">

    @stack('scripts-head')
</head>

<body>

    <header>
        <h1>THERMO PREDICT</h1>
        <p>@yield('subtitle', 'Monitoramento Inteligente da Cadeia Fria')</p>
    </header>

    <nav>
        @if (auth()->check() && auth()->user()->perfil === 'admin')
            <a href="{{ route('admin.clientes') }}"
                class="{{ request()->routeIs('admin.clientes') ? 'ativo' : '' }}">Lista de Clientes</a>
            <a href="{{ route('admin.pendencias') }}"
                class="{{ request()->routeIs('admin.pendencias') ? 'ativo' : '' }}">Aprovações Pendentes</a>
        @else
            <a href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard') ? 'ativo' : '' }}">Dashboard</a>
            <a href="{{ route('chamados.create') }}"
                class="{{ request()->routeIs('chamados.create') ? 'ativo' : '' }}">Novo Chamado</a>
            <a href="{{ route('suporte') }}" class="{{ request()->routeIs('suporte') ? 'ativo' : '' }}">Suporte</a>
            <a href="{{ route('relatorios') }}"
                class="{{ request()->routeIs('relatorios') ? 'ativo' : '' }}">Relatórios</a>
        @endif

        <form action="{{ route('logout') }}" method="POST" style="margin-left: auto; padding: 0;">
            @csrf
            <button type="submit"
                style="background: transparent; border: 1px solid rgba(255,255,255,0.5); color: white; padding: 8px 15px; border-radius: 8px; cursor: pointer; transition: 0.3s;">
                Sair
            </button>
        </form>
    </nav>

    @yield('content')

    <footer class="rodape">
        <p>THERMO PREDICT © {{ date('Y') }} • Monitoramento Inteligente da Cadeia Fria</p>
    </footer>

    @stack('scripts')
</body>

</html>
