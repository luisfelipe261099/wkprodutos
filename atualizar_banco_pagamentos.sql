-- ============================================
-- Script para atualizar formas de pagamento
-- e tipos de faturamento na tabela orcamentos
-- ============================================
-- Data: 2025-11-06
-- Descrição: Adiciona 'boleto' e novos prazos de pagamento
-- ============================================

-- 1. Adicionar 'boleto' nas formas de pagamento
ALTER TABLE `orcamentos` 
MODIFY COLUMN `forma_pagamento` 
ENUM('pix','debito','credito','dinheiro','faturamento','boleto') 
DEFAULT 'faturamento';

-- 2. Adicionar novos tipos de faturamento
ALTER TABLE `orcamentos` 
MODIFY COLUMN `tipo_faturamento` 
ENUM(
    'avista',
    '7',
    '15',
    '15_dias',
    '20',
    '20_dias',
    '21',
    '30',
    '30_dias',
    '45_dias',
    '60_dias',
    '90_dias',
    '15_30',
    '20_30',
    '21_30',
    '20_30_45',
    '28_35_42',
    '28_35_42_59',
    '28_35_45',
    '30_45_60'
) 
DEFAULT 'avista';

-- ============================================
-- Verificar as alterações
-- ============================================
SHOW COLUMNS FROM `orcamentos` LIKE 'forma_pagamento';
SHOW COLUMNS FROM `orcamentos` LIKE 'tipo_faturamento';

-- ============================================
-- FIM DO SCRIPT
-- ============================================

