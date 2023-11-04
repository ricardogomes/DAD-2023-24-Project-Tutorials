import { defineConfig } from "vitepress";

// https://vitepress.dev/reference/site-config
export default defineConfig({
  head: [["link", { rel: "icon", href: "/assets/favicon.ico" }]],
  title: "Project Tutorials",
  description: "Tutorials related to the DAD 2023/24 course project",
  cleanUrls: true,
  lastUpdated: true,
  themeConfig: {
    search: {
      provider: "local",
    },
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: "Home", link: "/" },
      {
        text: "Tutorials",
        items: [
          {
            text: "Project",
            items: [
              {
                text: "Connect to the VM",
                link: "./connect.md",
              },
              { text: "Install Components", link: "/install" },
              { text: "Deploy Code", link: "/deploy" },
              {
                text: "Intermediate Submission",
                link: "./intermediate.md",
              },
            ],
          },
          {
            text: "Auxiliary",
            items: [
              {
                text: "IPLeiria - VPN",
                link: "./vpn.md",
              },
              {
                text: "Laravel Authentication - Passport",
                link: "./passport.md",
              },
              {
                text: "Web Sockets - SocketIO",
                link: "./socketio.md",
              },
              {
                text: "Windows Subsystem for Linux",
                link: "./wsl.md",
              },
            ],
          },
        ],
      },
      {
        text: "Resources",
        items: [
          {
            text: "Infrastructure",
            items: [
              {
                text: "Nginx Documentation",
                link: "https://nginx.org/en/docs/",
              },
              {
                text: "MySQL Documentation",
                link: "https://dev.mysql.com/doc/",
              },
              {
                text: "NodeJS Documentation",
                link: "https://nodejs.org/en/docs",
              },
              {
                text: "PM2 Documentation",
                link: "https://pm2.keymetrics.io/docs/usage/quick-start/",
              },
            ],
          },
          {
            text: "Tech Stack",
            items: [
              {
                text: "Laravel Documentation",
                link: "https://laravel.com/docs/10.x/readme",
              },
              {
                text: "VueJS Documentation",
                link: "https://vuejs.org/guide/introduction.html",
              },
              {
                text: "SocketIO Documentation",
                link: "https://socket.io/docs/v4/",
              },
            ],
          },
        ],
      },
    ],
    footer: {
      message: "IPLeiria | ESTG | EI | DAD 2023/24",
    },
    socialLinks: [
      {
        icon: "github",
        link: "https://github.com/ricardogomes/DAD-2023-24-Project-Tutorials",
      },
    ],
  },
});
