# Web-Based Trading Platform 

**1\. SYSTEM OVERVIEW**

A web-based trading platform that:

* Allows users to trade assets  
* Supports real money deposits via e-wallets and bank payments  
* Uses PayMongo as the primary payment gateway  
* Implements secure wallet ledger and admin-controlled withdrawals  
* Implements KYC (Know Your Customer)   
* Ensures secure, auditable transactions   
* Provides admin oversight and fraud monitoring   
* Legal compliance to Bangko Sentral ng Pilipinas (BSP) and Data privacy rules under National Privacy Commission (NPC) 

  ### **KYC (Know Your Customer)**

* Valid ID upload  
* Selfie verification  
* Personal details (name, address, DOB)  
* Admin approval workflow

###      **AML (Anti-Money Laundering)**

* Transaction monitoring  
* Suspicious activity flags  
* Threshold alerts (large deposits/withdrawals)

###      **Data Privacy**

* User consent  
* Secure storage of personal data  
* Data access logging

System acts as:

* Trading platform  
* Payment-integrated system (NOT a bank)

   
**2\. CORE MODULES** 

## 2.1. User & Identity Module

* Registration / Login  
* Role-based access (Admin / Trader)  
* KYC verification (required for withdrawals)  
* Account Status (Verified/Not verified)

## 2.2. Wallet & Ledger Module (CRITICAL)

* Ledger-based accounting  
* Balance computed from transactions  
* No manual balance editing

## 2.3. Payment Integration Module (UPDATED)

### Supported Payment Types:

####  E-Wallet

* GCash (via PayMongo)

####  Bank Payments

* Online bank payments (via PayMongo supported methods)  
* Extensible for:  
  * Direct bank APIs (future)  
  * OTC / manual bank transfers (fallback)

### Payment Features:

* Payment source creation  
* Redirect checkout  
* Webhook verification  
* Idempotent processing

## 2.4. Trading Module

* Buy / Sell assets  
* Balance validation  
* Order recording

## 2.5. Portfolio Module

* Holdings tracking  
* Profit / Loss computation  
* P/L Calculator

## 2.6. Admin Module

* User management  
* KYC approval  
* Payment monitoring  
* Withdrawal approval  
* Fraud Alert

## 2.7. Risk & Fraud Module

* Detect suspicious transactions  
* Flag unusual activity  
* Unusual Volume of users  
* Rapid transactions

## 

## 2.8. Audit Logs Module

* Immutable logs for all actions

**3\. DATABASE STRUCTURE**

**users:**

CREATE TABLE users (  
    id INT AUTO\_INCREMENT PRIMARY KEY,  
    name VARCHAR(100),  
    email VARCHAR(100) UNIQUE,  
    password VARCHAR(255),  
    role ENUM('admin','trader'),  
    status ENUM('pending','verified','suspended') DEFAULT 'pending',  
    created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP  
);

**kyc\_verifications:**

CREATE TABLE kyc\_verifications (  
    id INT AUTO\_INCREMENT PRIMARY KEY,  
    user\_id INT,  
    id\_type VARCHAR(50),  
    id\_number VARCHAR(100),  
    id\_image TEXT,  
    selfie\_image TEXT,  
    status ENUM('pending','approved','rejected') DEFAULT 'pending',  
    reviewed\_by INT NULL,  
    created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,  
    FOREIGN KEY (user\_id) REFERENCES users(id)  
);

**fraud\_flags:**

CREATE TABLE fraud\_flags (  
    id INT AUTO\_INCREMENT PRIMARY KEY,  
    user\_id INT,  
    reason TEXT,  
    severity ENUM('low','medium','high'),  
    status ENUM('open','reviewed','closed') DEFAULT 'open',  
    created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP  
);

**payment\_methods:**

CREATE TABLE payment\_methods (  
    id INT AUTO\_INCREMENT PRIMARY KEY,  
    name VARCHAR(50), \-- GCash, BDO, BPI  
    type ENUM('ewallet','bank'),  
    provider VARCHAR(50), \-- paymongo  
    status ENUM('active','inactive') DEFAULT 'active'  
);

**payments:**

CREATE TABLE payments (  
    id INT AUTO\_INCREMENT PRIMARY KEY,  
    user\_id INT,  
    amount DECIMAL(15,2),  
    method\_id INT,  
    provider VARCHAR(50), \-- paymongo  
    external\_reference VARCHAR(255), \-- source\_id or payment\_id  
    payment\_type ENUM('gcash','bank'),  
    status ENUM('pending','completed','failed'),  
    idempotency\_key VARCHAR(255),  
    created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,  
    FOREIGN KEY (user\_id) REFERENCES users(id),  
    FOREIGN KEY (method\_id) REFERENCES payment\_methods(id)  
);

**bank\_accounts:**

CREATE TABLE bank\_accounts (  
    id INT AUTO\_INCREMENT PRIMARY KEY,  
    user\_id INT,  
    bank\_name VARCHAR(100),  
    account\_name VARCHAR(100),  
    account\_number VARCHAR(100),  
    created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,  
    FOREIGN KEY (user\_id) REFERENCES users(id)  
);

 **wallet\_ledger:**

CREATE TABLE wallet\_ledger (  
    id INT AUTO\_INCREMENT PRIMARY KEY,  
    user\_id INT,  
    type ENUM('deposit','withdraw','trade\_buy','trade\_sell'),  
    amount DECIMAL(15,2),  
    balance\_after DECIMAL(15,2),  
    reference\_id INT,  
    created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP  
);

**4\. PAYMENT FLOWS** 

**DEPOSIT FLOW** 

### Step-by-step:

1. User selects:  
   * GCash  
   * Bank  
2. System creates PayMongo Source:  
   * type \= `gcash` OR `bank`  
3. Save:  
   * `external_reference = source_id`  
   * `status = pending`  
4. Redirect user to checkout page  
5. Wait for webhook  
6. On webhook:  
   * Verify event  
   * Check idempotency  
   * Insert ledger entry  
   * Update payment status

**WITHDRAWAL FLOW** 

### Step 1: User Request

* Select:  
  * Bank account  
  * E-wallet number

### Step 2: Validation

* Check:  
  * KYC approved  
  * Sufficient balance

### Step 3: Admin Approval

* Approve / Reject

### Step 4: Processing

#### Option A (Recommended)

* Manual transfer via:  
  * Bank  
  * GCash

#### Option B (Advanced)

* Integrate payout API (future)

### Step 5: Finalization

* Insert ledger entry  
* Mark withdrawal completed

**5\. DASHBOARD** 

**ADMIN DASHBOARD**

### **Pages:**

* Dashboard (stats)  
* Users Management  
* Payments Monitoring  
* Withdrawals Approval  
* Trading Logs  
* Reports  
* Audit Logs  
* Payment Method Monitoring  
* Bank transaction tracking  
* Webhook logs viewer

**TRADER DASHBOARD**

### **Pages:**

* Dashboard (balance \+ summary)  
* Wallet (deposit/withdraw)  
* Trade (buy/sell)  
* Portfolio  
* Transactions  
* Market Data  
* Select payment method (GCash / Bank)  
* Manage bank accounts  
* Payment status tracking

**6\. SECURITY**

* HTTPS (SSL certificate)  
* Password hashing (`bcrypt`)  
* CSRF protection  
* SQL injection protection (prepared statements)  
* Rate limiting (login/payment endpoints)  
* Webhook signature validation  
* File upload validation (for IDs)

# **7\. INFRASTRUCTURE (MINIMUM)**

* VPS hosting (NOT shared hosting)  
* Daily database backups  
* Error logging system  
* Separate environment config (`.env`)

**DEVELOPMENT CHECKLIST**

# **1\. PROJECT FOUNDATION**

* **Set up local dev environment (XAMPP / Laragon / VPS-ready structure)**  
* **Create MySQL database**  
* **Configure `db.php` (PDO preferred)**  
* **Create `.env-like config file`:**  
  * **API keys (PayMongo)**  
  * **Base URLs**  
  * **Environment (sandbox/live)**

## **Folder Structure**

* **`/admin`**  
* **`/trader`**  
* **`/api`**  
* **`/includes`**  
* **`/assets/css`**  
* **`/assets/js`**  
* **`/uploads/kyc`**

# **2\. DATABASE SETUP (FINAL TABLES)**

## **Core System**

* **users**  
* **assets**  
* **orders**  
* **portfolios**

## **Financial System**

* **wallets (or ledger system only)**  
* **wallet\_ledger (CRITICAL)**  
* **payments**  
* **payment\_methods**  
* **payment\_logs**  
* **withdrawals**  
* **bank\_accounts**

## **Compliance & Security**

* **kyc\_verifications**  
* **audit\_logs**  
* **fraud\_flags**

# **3\. AUTHENTICATION SYSTEM**

## **Core Auth**

* **Register system**  
* **Login system**  
* **Logout system**  
* **Password hashing (`password_hash`)**

## **Access Control**

* **Admin-only restriction**  
* **Trader-only restriction**  
* **Session protection**

# **4\. USER PROFILE MODULE**

* **Profile view**  
* **Edit profile**  
* **Change password**  
* **Upload profile image (optional)**

# **5\. KYC MODULE (REQUIRED FOR REAL MONEY)**

## **User Side**

* **Upload valid ID**  
* **Upload selfie**  
* **Fill personal info**

## **Admin Side**

* **Approve KYC**  
* **Reject KYC**  
* **View documents**

## **Rules**

* **No withdrawal if NOT verified**

# **6\. WALLET \+ LEDGER SYSTEM (CRITICAL)**

## **Ledger Setup**

* **Create `wallet_ledger.php` helper**  
* **All balance changes go through ledger ONLY**

## **Ledger Actions**

* **deposit entry**  
* **withdrawal entry**  
* **trade buy entry**  
* **trade sell entry**

## **Rules**

* **Balance \= computed from ledger**  
* **No manual balance updates**

## **Setup**

* **Get API keys**  
* **Configure secret/public keys**  
* **Set sandbox mode first**

## **Deposit Flow**

* **Create payment source (GCash or Bank)**  
* **Store `external_reference`**  
* **Save payment record (pending)**  
* **Redirect user to PayMongo checkout**

## **Webhook System**

* **Create `webhook.php`**  
* **Receive JSON payload**  
* **Verify signature**  
* **Check event type**  
* **Prevent duplicate processing (idempotency)**

## **Wallet Update**

* **Insert ledger entry ONLY after webhook confirmation**  
* **Update payment status \= completed**

# **8\. BANK PAYMENT SUPPORT (EXTENDED)**

## **Setup**

* **Add bank as payment method type**  
* **Store bank accounts per user**

## **Deposit Handling**

* **Bank via PayMongo supported methods OR manual verification fallback**

## **Withdrawal Handling**

* **Admin processes bank transfers manually OR via payout API (if available)**

# **9\. WITHDRAWAL MODULE**

## **User Side**

* **Withdrawal request form**  
* **Select bank/e-wallet account**  
* **Amount validation**

## **System Validation**

* **KYC approved check**  
* **Wallet balance check**

## **Admin Side**

* **Approve withdrawal**  
* **Reject withdrawal**  
* **Mark completed**

## **Ledger Entry**

* **Record withdrawal in ledger**

# **10\. TRADING MODULE**

## **Buy**

* **Validate wallet balance**  
* **Create order**  
* **Deduct via ledger**  
* **Update portfolio**

## **Sell**

* **Validate holdings**  
* **Create order**  
* **Add funds via ledger**  
* **Update portfolio**

# **11\. PORTFOLIO MODULE**

* **Show holdings**  
* **Show average price**  
* **Show profit/loss**  
* **Real-time price display (optional)**

# **12\. PAYMENT MODULE**

## **Features**

* **Payment methods (GCash, Bank)**  
* **Payment status tracking**  
* **External reference tracking**  
* **Payment logs**

# **13\. ADMIN DASHBOARD**

* **User management**  
* **KYC approvals**  
* **Payment monitoring**  
* **Withdrawal approvals**  
* **Trading logs**  
* **Fraud flags**  
* **Audit logs**

# **14\. TRADER DASHBOARD**

* **Wallet overview**  
* **Deposit button**  
* **Withdraw button**  
* **Trade interface**  
* **Portfolio view**  
* **Transaction history**

# **15\. AUDIT LOGGING SYSTEM**

* **Log all login/logout**  
* **Log all payments**  
* **Log all trades**  
* **Log admin actions**

# **16\. FRAUD DETECTION (BASIC VERSION)**

* **Large deposit detection**  
* **Rapid transactions detection**  
* **Multiple failed payments**  
* **Flag suspicious users**

# **17\. SECURITY CHECKLIST (NON-NEGOTIABLE)**

## **Core Security**

* **Prepared statements (PDO/MySQLi)**  
* **Password hashing**  
* **Session security**

## **Payment Security**

* **Webhook signature verification**  
* **Idempotency handling**  
* **Amount validation**

## **File Security**

* **Secure KYC uploads**  
* **File type validation**

# **18\. TESTING CHECKLIST**

## **Authentication**

* **Register/login/logout**

## **KYC**

* **Upload / approve / reject**

## **Payments**

* **GCash success**  
* **Bank payment success**  
* **Failed payment handling**  
* **Duplicate webhook test**

## **Trading**

* **Buy/sell success/failure**

## **Withdrawal**

* **Approval flow**  
* **Rejection flow**

# **19\. DEPLOYMENT CHECKLIST**

* **Move to VPS (recommended)**  
* **Enable HTTPS (SSL)**  
* **Set production config**  
* **Disable debug mode**  
* **Secure API keys**  
* **Enable backups**

# **PRIORITY BUILD ORDER (DO THIS EXACTLY)**

1. **Database**  
2. **Auth system**  
3. **KYC system**  
4. **Ledger wallet**  
5. **Manual wallet test**  
6. **PayMongo deposit integration**  
7. **Webhook system**  
8. **Bank payment support**  
9. **Withdrawals**  
10. **Trading system**  
11. **Portfolio**  
12. **Admin dashboard**  
13. **Audit \+ fraud system**  
14. **Deployment**

 

