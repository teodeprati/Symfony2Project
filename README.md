# User-CDA
# **Symfony User Management System**

A user management system built with Symfony, featuring authentication via a web interface (Twig) and an API (JWT for admins). The interface is styled with **Bootstrap**.

---

## **Features**

- **Authentication**:
  - Web login for all users
  - API login secured with JWT (admins only)
- **CRUD Operations**:
  - Create, read, update, and delete users
- **Security**:
  - Role-based access: `ROLE_USER` and `ROLE_ADMIN`
  - Restricted access to authenticated users
- **Modern UI**:
  - Responsive interface styled with **Bootstrap**

---

## **Getting Started**

### **Prerequisites**

- PHP 8.1 or higher
- Composer
- Node.js and Yarn (for assets)
- A database compatible with Doctrine (MySQL)
- OpenSSL (for JWT keys)

### **Installation**

1. **Clone the repository**
2. **Install PHP dependencies** composer install
3. **Install Node.js depencecies** yarn install
4. **Configure the database** Update .env
5. **Create the database and run migrations**
6. **Compile assets** run : yarn encore dev
7. **Generate JWT keys**
     - mkdir -p config/jwt
     - openssl genrsa -out config/jwt/private.pem -aes256 4096
     - openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
   
8.**Update the .env file with the JWT configuration**





