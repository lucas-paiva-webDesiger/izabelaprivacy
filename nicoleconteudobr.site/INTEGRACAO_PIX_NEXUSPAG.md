# Checkout PIX NexusPag

## Importante
O checkout **não funciona abrindo o HTML diretamente com `file://`**. O navegador bloqueia a chamada `fetch()` para `../api/pix.php`, conforme o erro de Cross-Origin mostrado no DevTools.

O site precisa estar sendo servido por HTTP/HTTPS e o PHP precisa executar no servidor.

## Produção
1. Envie `nicoleconteudobr.site/api/` para a hospedagem.
2. Envie `nicoleconteudobr.site/nicolle/` para a hospedagem.
3. Configure `NEXUSPAG_API_KEY` no ambiente PHP do servidor.
4. Abra a página pelo domínio, por exemplo `https://SEU-DOMINIO/nicolle/home57cf.html`.
5. Não abra `C:\...\home57cf.html` diretamente.

## Teste local com PHP
Na pasta `nicoleconteudobr.site`, execute:

```bash
php -S 127.0.0.1:8080
```

Depois acesse:

```text
http://127.0.0.1:8080/nicolle/home57cf.html
```

## NexusPag
A API usa `POST https://nexuspag.com/api/pix/create`, autenticação pelo header `x-api-key` e retorna os dados PIX dentro de `transaction.pix_copia_cola` e `transaction.qr_code_base64`.
