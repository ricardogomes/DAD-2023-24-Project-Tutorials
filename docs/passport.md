---
outline: deep
---

# Laravel Authentication with Passport

In this tutorial we will setup Passport as the authentication mechanism for our Laravel REST API.

We will use Passports' password grant which allows us to generate an authentication token by validating the user credentials (username + password).

:::tip Note
Most of the code examples can be found on the [Tutorial Github Repository](https://github.com/ricardogomes/DAD-2023-24-Project-Tutorials)) under the `code\laravel` folder.

**All the commands are to be ran locally on our development environment.**
:::

## Setup

First we need to add the dependency to Laravel.

::: code-group

```bash [Laragon]
composer require laravel/passport
```

```bash [Docker | Sail]
sail composer require laravel/passport
```

:::

Besides adding the dependencies to the `vendor` folder composer also created a set of database migrations for the tables that Passport requires, so we need to run those migrations. After the migration is executed we need to run the `passport:install` command to setup the base data for Passport:

::: code-group

```bash [Laragon]
php artisan migrate
php artisan passport:install
```

```bash [Docker | Sail]
sail artisan migrate
sail artisan passport:install
```

:::

The last command will output a set of IDs and Secrets, we need to capture the one for the password grant. Here's and example:

```bash
➜ sail artisan passport:install
Encryption keys generated successfully.
Personal access client created successfully.
Client ID: 1
Client secret: hk4d89GozTJnktV6sodSc0Dit9Fy8LE9kGnTTjGV
Password grant client created successfully.
Client ID: 2
Client secret: Nu5CI5PPUyQTUZfSaL03v50JNPsJs0QVxcdX82d
```

There are multiple ways to capture these elements, the suggestion here is to place them on the `.env` file. Add the following elements to the `.env` file, chaging the id / secret accordingly:

```ini
PASSPORT_URL="<YOUR API BASE URL | http://project.test | http://localhost>"
PASSPORT_PASSWORD_GRANT_ID="2"
PASSPORT_PASSWORD_GRANT_SECRET="Nu5CI5PPUyQTUZfSaL03v50JNPsJs0QVxcdX82db"
```

## Configuration

Next we need to effect some changes to start using Passport.

The first thing is to tell Laravel that for all `/api` calls we want
Passport to be the default authentication provider. To do this apply these changes to the `config/auth.php` file:

<<< ../code/laravel/config/auth.php#guards{6-9 php:line-numbers}

Next we need to update the model that is going to be used for authentication. Apply the following changes to the `App\Models\User.php` file:

<<< ../code/laravel/app/Models/User.php{9,13 php:line-numbers}

## Usage

Now that we have Passport setup lets use it. To do so we will create a new controller to handle the login process. Run the following command to create that controller:

::: code-group

```bash [Laragon]
php artisan make:controller auth/AuthController
```

```bash [Docker | Sail]
sail artisan make:controller auth/AuthController
```

:::

Then add the following code (keep in mind we are using the variables we defined in the `.env` file):

<<< ../code/laravel/app/Http/Controllers/auth/AuthController.php

Finally we can add a few routes, and change the ones we want to be authenticated, on the `routes/api.php` file:

<<< ../code/laravel/routes/api.php{3,8-12,14,15,17,18 php:line-numbers}

## Using the Passport tokens

Now to access our `api/categories` routes we need to present a `token`, and to get that `token` we need to call the `api/auth/login` endpoint with a username and password.

These are some examples of code to call the login endpoint:

::: code-group

```bash [curl]
curl --request POST \
  --url http://localhost/api/auth/login \
  --header 'Content-Type: application/json' \
  --data '{
  "username": "a1@mail.pt",
  "password": "123"
}'
```

```js [JS Axios]
import axios from "axios";

const options = {
  method: "POST",
  url: "http://localhost/api/auth/login",
  headers: { "Content-Type": "application/json" },
  data: { username: "a1@mail.pt", password: "123" },
};

try {
  const { data } = await axios.request(options);
  console.log(data);
} catch (error) {
  console.error(error);
}
```

:::

This request should return something like this, and we are interested in the `access_token` property:

```json
{
  "token_type": "Bearer",
  "expires_in": 31622400,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIyIiwianRpIjoiN2Y5NDZjNWM5ODFjM2MzZjExZDUyOWQ5ODEzOWUwYmVjZGQyMDYzYjNiYjg0MmFjZjQ4NTljMjI1MzBmNzg5M2UxMjhkMjIxMzhkYjBkZmEiLCJpYXQiOjE2OTg2NzU5NTQuNjY4NjksIm5iZiI6MTY5ODY3NTk1NC42Njg2OTQsImV4cCI6MTczMDI5ODM1NC42MjQ1OTYsInN1YiI6IjEiLCJzY29wZXMiOltdfQ.RUIYrwAJCz5SPRes5fTCctzxt3Gjr676TvtEIsgiNLRB_usKm0T7E5GmIUFUUXAiQKmeUV8QL9H-OelVuI6ow9pGYVoEnwI1G_8_vjielu_v8aCI4jlPgNafI1ka-MTZGcCY82QXZpj5QEfHojau13KMHKgEtaR1NHzmvvvr8kkMMIKu3D2q0KiWbrLpCoqozD1qulOh_9Y_-wZe5aTm2ag8dAQZxk7XHJzOdsmSI862Lq9Ek6Um0afDsgoqJMdtd_H0Mz0__2e42En4oZM8mJLirGIcs3pQj7l_csdIgHn62-P8ODu7mD9EByKAWeqr04nxm9LUAQNhrb3SecCZRfqgQA8JDjTxLxOHLbNc_UHsJq7S7XXOcdZfP77N1GH2MreL9D4fbYxrnAXcr3CSUc8F_05pA6K6ejI02N29xf67KwWad-gctdek03VDBOhnZ9SRRCrsnInnMijZA2Cg7Xo6S6h2k5lMUmHJjbb4Jx2WpJa72aqvmSJmGfY0bD0y2NaxJ81vqF-BYhfWD8A8pBchl8P1DorQZ2wayU3-4zJll70yMCjvZTab-HhqodAQWDH4A1fA7mmrCNPrXdK4tKS85tgqBcZ7IyhtuZrliVI2KHpSDc7vHaOf1UZ9lbm13u2Gs3ALJIPFrM8Ejmcoj5wW33L67PbOhLpZNQ7NSks",
  "refresh_token": "def502000dc8b07f9e03942887320ac16ae5521b70e9d49bfb873e8337b45d7ddf3a9503eb4692194a063fd180dbf6da0d24ad1ac52eee792ecbac64ca571908072aa15f0fff3ed73417aa2dbb4fe0104e1505dc637b7c9d4e76035350983a4f1cb5a143df43d8cb1cc14fb909b4b522007599201ba9f935e5cf86e61a8f886baf384a9ac4faa4b222ef8b00f822eac71fc6652572ce74de24fd61b442e623a20c3ca38f6586f2f428897ada1f59a23f598b4102aca3a043c4c2f3b2eb22a6fd7b80b71f9613a338bd153a385355275523fbfe1d715103a9989545ebe10b5eb00913d80e4b40d5c2ca7f386d2863c5f028692c1f9852e66f3f6fc7776a315e7d82bb28a6dad15b7fb7292d72780e83a57de5d518a85a88930bcda07dce924ce34b4fab4307490f8f430e6523d7b0c0e3208a9a1d7afae23dd9aa14932a822505b91d8c0907a1cceade9f6d58e847a9c7466f0909917adfd70b134dd1a1ddf4ca03"
}
```

We can now call an authenticated endpoint (ie: `/api/categories`) like this:

::: code-group

```bash [curl]
curl --request GET \
  --url http://localhost/api/categories \
  --header 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIyIiwianRpIjoiN2Y5NDZjNWM5ODFjM2MzZjExZDUyOWQ5ODEzOWUwYmVjZGQyMDYzYjNiYjg0MmFjZjQ4NTljMjI1MzBmNzg5M2UxMjhkMjIxMzhkYjBkZmEiLCJpYXQiOjE2OTg2NzU5NTQuNjY4NjksIm5iZiI6MTY5ODY3NTk1NC42Njg2OTQsImV4cCI6MTczMDI5ODM1NC42MjQ1OTYsInN1YiI6IjEiLCJzY29wZXMiOltdfQ.RUIYrwAJCz5SPRes5fTCctzxt3Gjr676TvtEIsgiNLRB_usKm0T7E5GmIUFUUXAiQKmeUV8QL9H-OelVuI6ow9pGYVoEnwI1G_8_vjielu_v8aCI4jlPgNafI1ka-MTZGcCY82QXZpj5QEfHojau13KMHKgEtaR1NHzmvvvr8kkMMIKu3D2q0KiWbrLpCoqozD1qulOh_9Y_-wZe5aTm2ag8dAQZxk7XHJzOdsmSI862Lq9Ek6Um0afDsgoqJMdtd_H0Mz0__2e42En4oZM8mJLirGIcs3pQj7l_csdIgHn62-P8ODu7mD9EByKAWeqr04nxm9LUAQNhrb3SecCZRfqgQA8JDjTxLxOHLbNc_UHsJq7S7XXOcdZfP77N1GH2MreL9D4fbYxrnAXcr3CSUc8F_05pA6K6ejI02N29xf67KwWad-gctdek03VDBOhnZ9SRRCrsnInnMijZA2Cg7Xo6S6h2k5lMUmHJjbb4Jx2WpJa72aqvmSJmGfY0bD0y2NaxJ81vqF-BYhfWD8A8pBchl8P1DorQZ2wayU3-4zJll70yMCjvZTab-HhqodAQWDH4A1fA7mmrCNPrXdK4tKS85tgqBcZ7IyhtuZrliVI2KHpSDc7vHaOf1UZ9lbm13u2Gs3ALJIPFrM8Ejmcoj5wW33L67PbOhLpZNQ7NSks' \
  --header 'Content-Type: application/json'
```

```js [JS Axios]
import axios from "axios";

const options = {
  method: "GET",
  url: "http://localhost/api/categories",
  headers: {
    Authorization:
      "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIyIiwianRpIjoiN2Y5NDZjNWM5ODFjM2MzZjExZDUyOWQ5ODEzOWUwYmVjZGQyMDYzYjNiYjg0MmFjZjQ4NTljMjI1MzBmNzg5M2UxMjhkMjIxMzhkYjBkZmEiLCJpYXQiOjE2OTg2NzU5NTQuNjY4NjksIm5iZiI6MTY5ODY3NTk1NC42Njg2OTQsImV4cCI6MTczMDI5ODM1NC42MjQ1OTYsInN1YiI6IjEiLCJzY29wZXMiOltdfQ.RUIYrwAJCz5SPRes5fTCctzxt3Gjr676TvtEIsgiNLRB_usKm0T7E5GmIUFUUXAiQKmeUV8QL9H-OelVuI6ow9pGYVoEnwI1G_8_vjielu_v8aCI4jlPgNafI1ka-MTZGcCY82QXZpj5QEfHojau13KMHKgEtaR1NHzmvvvr8kkMMIKu3D2q0KiWbrLpCoqozD1qulOh_9Y_-wZe5aTm2ag8dAQZxk7XHJzOdsmSI862Lq9Ek6Um0afDsgoqJMdtd_H0Mz0__2e42En4oZM8mJLirGIcs3pQj7l_csdIgHn62-P8ODu7mD9EByKAWeqr04nxm9LUAQNhrb3SecCZRfqgQA8JDjTxLxOHLbNc_UHsJq7S7XXOcdZfP77N1GH2MreL9D4fbYxrnAXcr3CSUc8F_05pA6K6ejI02N29xf67KwWad-gctdek03VDBOhnZ9SRRCrsnInnMijZA2Cg7Xo6S6h2k5lMUmHJjbb4Jx2WpJa72aqvmSJmGfY0bD0y2NaxJ81vqF-BYhfWD8A8pBchl8P1DorQZ2wayU3-4zJll70yMCjvZTab-HhqodAQWDH4A1fA7mmrCNPrXdK4tKS85tgqBcZ7IyhtuZrliVI2KHpSDc7vHaOf1UZ9lbm13u2Gs3ALJIPFrM8Ejmcoj5wW33L67PbOhLpZNQ7NSks",
    "Content-Type": "application/json",
  },
};

try {
  const { data } = await axios.request(options);
  console.log(data);
} catch (error) {
  console.error(error);
}
```

## REST Client Collections

To simplify the tests, on the tutorial repository there's a folder called `rest_collections` with request collections for [Postman](https://www.postman.com/downloads/) and [Bruno](https://www.usebruno.com/).
