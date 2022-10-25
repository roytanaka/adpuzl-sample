# 🧩 AdPuzl <!-- omit in toc -->

Quickly launch powerful social media ad campaigns that get results.

## 🗒 Table of Contents <!-- omit in toc -->

- [1. ✅ Prerequisites](#1--prerequisites)
- [2. ⬇️ Installation Instructions](#2-️-installation-instructions)
- [3. 🔑 Local SSL Development Environment](#3--local-ssl-development-environment)
  - [3.1. Install `mkcert`](#31-install-mkcert)
    - [3.1.1. macOs](#311-macos)
    - [3.1.2. Windows](#312-windows)
  - [3.2. Generate Certificates](#32-generate-certificates)
  - [3.3. Modify Hosts File](#33-modify-hosts-file)
- [4. 🤫 Environment Variables](#4--environment-variables)
- [5. 🔠 Font Awesome Local Dev Environment Variable](#5--font-awesome-local-dev-environment-variable)
    - [5.1 MacOS/Linux](#51-macoslinux)
    - [5.2 Windows](#52-windows)
- [6. 🚜 Build & Hot-Reloads for Development](#6--build--hot-reloads-for-development)

## 1. ✅ Prerequisites

- Node.js version 16 or higher

### 1.1. Windows

When cloning this repository on Windows, you may run into an ESLint error when Git on Windows defaults to converting line breaks to [CRLF](https://developer.mozilla.org/en-US/docs/Glossary/CRLF). To prevent this, change your Git configuration for `autocrlf` to false. 

```sh
git config --global core.autocrlf false
```
For more information, see [here](https://stackoverflow.com/a/67305884/16238731). 

## 2. ⬇️ Installation Instructions

Fork this repository to your GitHub account by clicking the Fork button in the upper right corner repo page. You can also fork this repo using [GitHub CLI](https://cli.github.com/manual/gh_repo_fork).

![Git fork](https://user-images.githubusercontent.com/43554947/165976080-479f7ae8-cce9-4769-bd38-f213b4d835ec.png)

Clone to your machine for local development.

**Clone repository**

```sh
git clone https://github.com/<YOUR_USERNAME>/adpuzl.git
```

**List the current configured remote repository for your fork**

```sh
cd adpuzl
git remote -v
# > origin  https://github.com/<YOUR_USERNAME>/adpuzl.git (fetch)
# > origin  https://github.com/<YOUR_USERNAME>/adpuzl.git (push)
```

**Specify a new remote upstream repository that will be synced with the fork**

```sh
git remote add upstream https://github.com/sidi-io/adpuzl.git
```

**Verify the new upstream repository you've specified for your fork**

```sh
git remote -v
# > origin    https://github.com/<YOUR_USERNAME>/adpuzl.git (fetch)
# > origin    https://github.com/<YOUR_USERNAME>/adpuzl.git (push)
# > upstream  https://github.com/sidi-io/adpuzl.git (fetch)
# > upstream  https://github.com/sidi-io/adpuzl.git (push)
```

**cd into directory**

```sh
cd adpuzl
```

**Install dependencies**

```sh
npm install
```

## 3. 🔑 Local SSL Development Environment

Running the local server in `https` is required to consume Meta's (Facebook's) Marketing API that enforces the app be served via `https`. Use the following instructions to create local SSL certificates and modify your host file for local development using SSL.

### 3.1. Install `mkcert`

[mkcert](https://github.com/FiloSottile/mkcert) is a simple tool for making locally-trusted development certificates. It requires no configuration.

#### 3.1.1. macOs

**On macOS, use [Homebrew](https://brew.sh/)**

```sh
brew install mkcert
brew install nss # if you use Firefox
```

#### 3.1.2. Windows

**On Windows, use [Chocolatey](https://chocolatey.org)**

```
choco install mkcert
```

**or use Scoop**

```
scoop bucket add extras
scoop install mkcert
```

or build from source (requires Go 1.10+), or use [the pre-built binaries](https://github.com/FiloSottile/mkcert/releases).

If you're running into permission problems try running `mkcert` as an Administrator.

### 3.2. Generate Certificates

**Create local Certificate Authority**

```sh
mkcert -install
# > Created a new local CA 💥
# > The local CA is now installed in the system trust store! ⚡️
# > The local CA is now installed in the Firefox trust store (requires browser restart)! 🦊
```

**In case of the following (Windows) follow this [workaround](https://github.com/FiloSottile/mkcert/issues/231#issuecomment-695776701)**

```
# > Error cert: Failed adding cert: Access is denied.
```

**Create `cert` directory at root level of project**

```sh
mkdir cert
```

**Make adpuzl certificates**

```sh
mkcert -key-file ./cert/adpuzl.local-key.pem -cert-file ./cert/adpuzl.local.pem adpuzl.local "*.adpuzl.local" localhost 127.0.0.1 ::1

# > Created a new certificate valid for the following names 📜
# >  - "adpuzl.local"
# >  - "*.adpuzl.local"
# >  - "localhost"
# >  - "127.0.0.1"
# >  - "::1"

# > The certificate is at "./cert/adpuzl.local.pem" and the key at "./cert/adpuzl.local-key.pem" ✅
```

### 3.3. Modify Hosts File

Add an `adpuzl.local` entry into your hosts file to redirect your browser to the local dev server. Learn more about [how hosts file works](https://pressidium.com/blog/the-hosts-file-a-powerful-tool-for-users-and-developers/). **Recommended**: make a backup of your hosts file before modifying.

1. Locate the hosts file for your OS, see below
2. Open file in a text editor
3. Add the following to the hosts file:

```
# AdPuzl dev server redirect
127.0.0.1  adpuzl.local
```

> macOS hosts file location:
>
> `/etc/hosts`

> Windows hosts file location:
>
> `C:\Windows\System32\drivers\etc`

## 4. 🤫 Environment Variables

Copy the file `.env-example` and rename to `.env.local`

```sh
cp .env-example .env.local
```

## 5. 🔠 Font Awesome Local Dev Environment Variable

#### 5.1 MacOS/Linux

[Font Awesome Pro](https://fontawesome.com/how-to-use/on-the-web/setup/using-package-managers) uses the `.npmrc` file to register the Pro license. This file does not read variables from .env but the environment variables on the local machine. To include the token in the build process, add the following line to your `.bashrc` or `.zshrc` file in your home directory.

```bash
export FONTAWESOME_TOKEN=00000-0000-0000-0000-0000000
```

#### 5.2 Windows

[Add a local environment variable](https://docs.oracle.com/en/database/oracle/machine-learning/oml4r/1.5.1/oread/creating-and-modifying-environment-variables-on-windows.html#GUID-DD6F9982-60D5-48F6-8270-A27EC53807D0) to windows with

```
variable name: FONTAWESOME_TOKEN
variable value: 00000-0000-0000-0000-0000000
```

You will need to replace the placeholder token with the actual token. This can be found under _Environment Variables_ in the Site Settings for the web service provider. Save the config file and restart your terminal.

## 6. 🚜 Build & Hot-Reloads for Development

```
npm run dev
```

**Compiles and minifies for production**

```
npm run build
```

**Run your unit tests**

```
npm run test:unit
```

**Lints and fixes files**

```
npm run lint
```
