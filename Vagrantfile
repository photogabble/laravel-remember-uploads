# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/trusty64"
  config.vm.network "private_network", ip: "192.168.33.10"
  config.vm.synced_folder ".", "/vagrant_data"
  config.vm.provider "virtualbox" do |vb|
    # Setup VM with 1 CPU and 512MB of RAM, this should be
    # more than enough for development.
    vb.memory = "512"
  end
  config.vm.provision "shell", path: "./.vagrant/bootstrap.sh"
end
