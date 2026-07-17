# Changelog

All notable changes to `to-upper` will be documented in this file

## [1.3.0] - 2026-05-09
- Corrigido parametros e funções obsoletas em php8.4
- Atualizado Package para 8.4

## [1.2.0] - 2026-05-09
- Corrigido validação de ASCII

## [1.1.0] - 2026-04-28

- Adicionado comando Artisan `toupper:normalize` para normalização em massa de dados existentes
- Adicionados scopes `whereUpper()` e `orWhereUpper()` para busca case-insensitive
- Adicionados callbacks `beforeToUpper()` e `afterToUpper()` para personalização da transformação
- Adicionado macro `toupper()` no Query Builder para atualizações diretas no banco
- Adicionada detecção automática de encoding com `mb_detect_encoding()` e `mb_convert_encoding()`
- Corrigida detecção de morph attributes: agora usa `str_ends_with()` ao invés de `str_contains()` para evitar falsos positivos
- Adicionado suporte a casts do Eloquent: atributos com casts `array`, `json`, `object`, `collection` e `encrypted` não são convertidos
- Otimização de performance com cache das configurações mescladas
- Melhor cobertura de testes com casos de edge case

## 1.0.0 - 2025-04-01

- initial release
