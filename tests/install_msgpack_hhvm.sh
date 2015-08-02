#!/usr/bin/env bash
set -e

sudo apt-get install -y python-software-properties make git


# hhvm-dev

sudo add-apt-repository -y ppa:mapnik/boost
sudo apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x5a16e7281be7a449
echo deb http://dl.hhvm.com/ubuntu precise main | sudo tee /etc/apt/sources.list.d/hhvm.list
sudo apt-get update
sudo apt-get install -y hhvm-dev


# gcc 4.8

sudo add-apt-repository -y ppa:ubuntu-toolchain-r/test
sudo apt-get update
sudo apt-get install -y gcc-4.8 g++-4.8

sudo update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-4.8 60 \
                         --slave /usr/bin/g++ g++ /usr/bin/g++-4.8
sudo update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-4.6 40 \
                         --slave /usr/bin/g++ g++ /usr/bin/g++-4.6
sudo update-alternatives --set gcc /usr/bin/gcc-4.8


# Boost 1.49

sudo add-apt-repository -y ppa:mapnik/boost
sudo apt-get update
sudo apt-get install -y libboost1.49-dev libboost-regex1.49-dev \
  libboost-system1.49-dev libboost-program-options1.49-dev \
  libboost-filesystem1.49-dev libboost-thread1.49-dev


# Google glog

svn checkout http://google-glog.googlecode.com/svn/trunk/ google-glog
cd google-glog
./configure
make
sudo make install
cd ..


# JEMalloc 3.x

wget http://www.canonware.com/download/jemalloc/jemalloc-3.6.0.tar.bz2
tar xjvf jemalloc-3.6.0.tar.bz2
cd jemalloc-3.6.0
./configure
make
sudo make install
cd ..


# msgpack-hhvm

git clone https://github.com/reeze/msgpack-hhvm
cd msgpack-hhvm
hphpize && cmake . && make

echo "hhvm.dynamic_extension_path = `pwd`" | sudo tee -a /etc/hhvm/php.ini
echo "hhvm.dynamic_extensions[msgpack] = msgpack.so" | sudo tee -a /etc/hhvm/php.ini
