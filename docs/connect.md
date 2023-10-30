---
outline: deep
next:
  text: "Install Components"
  link: "./install"
---

# Connecting to the VM

Each group has their own Virtual Machine, and corresponding SSH Key (check your inbox for an email with instructions), to deploy the Project code.

## IPLeiria VPN

The Virtual Machine provided for this project is only accessable with the campus network or via the schools VPN. See how to configure it in [IPLeiria VPN](./vpn.md)

## SSH Connection

SSH Keys are a pair of files, the one with no extension is the private key and the one with the .pub extension is the public key. The public key was already added to the VM users' `.ssh/authorized_keys`, and so we need the private key to authenticate with the VM.

The first step is to make sure we can connect to our VM, and the suggested way to do this is via the `ssh` command. This works the same in Windows/Linux/MacOS systems, and should be present by default.

In order to connect to our VM run this command in a shell (PowerShell, cmd, bash, zsh, ...)

```bash
ssh dad@<VM_IP> -i <PATH_TO_VM_PRIVATE_KEY>
```

Where `<VM_IP>` sould be the IP Address of our VM (present in email) and the `<PATH_TO_VM_PRIVATE_KEY>` the relative path to our SSH key (send as an attachment on the same email).

## SSH Key Issues

Depending on how we setup our SSH Keys there might be some errors while attempting the connection above. The most common one is incorrect permitions of the actual files (or the private key file given its the one we'll be using). If we get an error stating something similar to "too open permissions" we can run the following command:

::: code-group

```powershell [Windows]
# run on PowerShell | cmd
icacls <PATH_TO_VM_PRIVATE_KEY> /inheritance:r /grant:r "%username%:R"
```

```bash [Linux | MacOS]
# run on bash | zsh
chmod 600 <PATH_TO_VM_PRIVATE_KEY>
```

:::

## Using SSH Agent <Badge type="warning" text="Advanced" />

Having to constantly point to the relative path of the private SSH key is frustrating, one option is to use the SSH Agent and store our key in memory. This removes the need for the `-i` parameter of the `ssh` command altogether.

To setup the private key using the SSH Agent run the following commands:

::: code-group

```powershell [Windows]
Set-Service -Name 'ssh-agent' -StartupType 'Manual'
Start-Service ssh-agent
ssh-add <PATH_TO_VM_PRIVATE_KEY>
```

```bash [Linux|MacOS]
eval "$(ssh-agent -s)"
ssh-add <PATH_TO_VM_PRIVATE_KEY>

```

:::

::: tip Adicional Resources

- [OpenSSH: Manual Pages](https://www.openssh.com/manual.html)
- [Key-based authentication in OpenSSH for Windows | Microsoft Learn](https://learn.microsoft.com/en-us/windows-server/administration/openssh/openssh_keymanagement)
- [Managing services - PowerShell | Microsoft Learn](https://learn.microsoft.com/en-us/powershell/scripting/samples/managing-services?view=powershell-7.3)
  :::
