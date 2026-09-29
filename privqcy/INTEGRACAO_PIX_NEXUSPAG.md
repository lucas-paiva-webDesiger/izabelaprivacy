# Integração PIX NexusPag

## Preços implementados
- R$ 9,90 -> amount 9.90
- R$ 14,90 -> amount 14.90
- R$ 24,90 -> amount 24.90
- R$ 32,90 -> amount 32.90

O backend valida os valores no servidor; o navegador não pode alterar a cobrança para um preço arbitrário.

## Configuração
Defina no servidor:
- `NEXUSPAG_API_KEY` = sua chave privada da NexusPag
- opcional: `NEXUSPAG_WEBHOOK_URL` = URL pública para webhook

Se a hospedagem não oferecer variáveis de ambiente, `api/config.php` contém um campo vazio para a chave. Preencha somente no servidor e nunca publique esse arquivo em repositório público.

## Endpoints locais
- `POST /api/pix.php` cria a cobrança
- `POST /api/status.php` consulta o status

A API NexusPag usa `POST /api/pix/create`, `x-api-key` e valores em reais com duas casas decimais.
