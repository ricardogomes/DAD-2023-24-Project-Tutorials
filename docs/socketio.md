---
outline: deep
---

# Web Sockets - Socket IO

Web Sockets are a mechanism to allow a full-duplex connection between client and server. They can be used for different use cases but for us we want to used them to implement real-time reactivity between different clients.

For example, when a vCard creates a transaction for another vCard, the destination vCard if online can be directly notified.

There are also multiple ways to implement Web Sockets, we will be using the [SocketIO](https://socket.io/) library, and we need two different implementations, a server, and the VueJS web socket client.

## Web Socket Server

We can start a simple NodeJS project with the following commands (we assume the commands are run in a `websockets` folder on our local machine):

::: code-group

```bash [NPM]
npm init
npm install --save-dev socket.io
```

```bash [PNPM]
pnpm init
pnpm install --save-dev socket.io
```

```bash [YARN]
yarn init
yarn add --dev socket.io
```

```bash [BUN]
bun init
bun add --dev socket.io
```

:::

Here is the full code for a simple echo web socket message:

Contents `/index.js`;

<<< ../code/websockets/index.js

And we can run the project with:

::: code-group

```bash [Node]
node index.js
```

```bash [NPM]
npm run start
```

```bash [PNPM]
pnpm run start
```

```bash [YARN]
yarn run start
```

```bash [BUN]
bun run start
```

## Vue JS Components

For VueJS, again there are multiple ways of implemeting a SocketIO client, we are going to use the `provide/inject` approach.
