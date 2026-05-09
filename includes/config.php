<?php
declare(strict_types=1);

const APP_ENV = 'local';
const APP_NAME = 'Vortex Trading Platform';
const APP_URL = 'http://localhost/Vortex';

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'vortex_trading';
const DB_USER = 'root';
const DB_PASS = '';

const AI_SERVICE_URL = 'http://127.0.0.1:8001/risk-score';
const PAYMENT_MODE = 'workflow'; // workflow | paymongo
const PAYMONGO_SECRET_KEY = '';
const PAYMONGO_WEBHOOK_SECRET = '';
const PAYMONGO_API_BASE = 'https://api.paymongo.com/v1';

const CSRF_KEY = 'vortex_csrf_token';
const SESSION_KEY_USER = 'vortex_user';
