<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THERMO PREDICT | Acesso</title>
    
    <link rel="stylesheet" href="[https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css](https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css)">
    
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <div class="container @if($errors->any() && !$errors->has('login_error')) right-panel-active @endif" id="container">
        
        <div class="form-container sign-up-container">
            <form action="{{ route('register.post') }}" method="POST">
                @csrf
                <h1>Criar Conta</h1>
                
                @if ($errors->any() && !$errors->has('login_error'))
                    <div class="alert-error">
                        <ul style="margin-left: 15px; padding-left: 10px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="input-group">
                    <i class="fa-solid fa-credit-card"></i>
                    <input type="text" id="campo_cnpj" name="cnpj" placeholder="CNPJ" required maxlength="18" value="{{ old('cnpj') }}">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-building"></i>
                    <input type="text" id="campo_razao_social" name="razao_social" placeholder="Razão Social" required value="{{ old('razao_social') }}">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="name" placeholder="Responsável" required value="{{ old('name') }}">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="E-mail corporativo" required value="{{ old('email') }}">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Senha (Mínimo de 6 caracteres)" required>
                </div>
                <button type="submit" class="btn-primary">Cadastrar</button>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <h1>Entrar no Sistema</h1>
                <p>Monitore sua cadeia fria em tempo real</p>

                @if ($errors->has('login_error'))
                    <div class="alert-error">
                        {{ $errors->first('login_error') }}
                    </div>
                @endif

                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="E-mail corporativo" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Senha" required>
                </div>
                <a href="#" class="forgot-password">Esqueceu sua senha?</a>
                <button type="submit" class="btn-primary">Acessar</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Já possui conta?</h1>
                    <p>Faça login para acessar seus equipamentos, sensores e relatórios.</p>
                    <button class="btn-ghost" id="signIn">Entrar</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>THERMO PREDICT</h1>
                    <p>Ainda não tem acesso? Crie sua conta e comece a monitorar agora mesmo.</p>
                    <button class="btn-ghost" id="signUp">Cadastrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>