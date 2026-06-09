<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THERMO PREDICT | Aguardando Ativação</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <style>
        .waiting-container {
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.05);
            width: 500px;
            max-width: 90%;
            padding: 50px 40px;
            text-align: center;
        }

        .icon-clock {
            font-size: 50px;
            color: #f39c12;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="waiting-container">
        <div class="icon-clock">⏳</div>
        <h1 style="font-size: 1.8rem;">Conta em Análise</h1>
        <p style="margin-top: 15px;">Seu cadastro foi recebido com sucesso e está com o status <strong>Pendente</strong>.
        </p>
        <p>Um administrador do sistema THERMO PREDICT precisa validar suas credenciais para liberar o acesso aos
            equipamentos.</p>

        <form action="{{ route('logout') }}" method="POST" style="padding: 0; margin-top: 30px;">
            @csrf
            <button type="submit" class="btn-primary" style="width: auto; padding: 12px 30px;">Voltar para o
                Início</button>
        </form>
    </div>
</body>

</html>
