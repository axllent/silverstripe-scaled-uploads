# Installation

```
composer require axllent/silverstripe-scaled-uploads
```

Please refer to the [Configuration.md](Configuration.md) for configuration options.

## Optimization

If you want to make use of the optimization features of this module you'll need to install the following packages on your server environment.

The package will use these optimizers if they are present on your system:

* JpegOptim
* Optipng
* Pngquant 2
* SVGO
* Gifsicle

Here's how to install all the optimizers on Ubuntu:

```sh
sudo apt-get install jpegoptim
sudo apt-get install optipng
sudo apt-get install pngquant
sudo npm install -g svgo
sudo apt-get install gifsicle
```