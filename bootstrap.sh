#!/bin/bash

# intranet / bastion host
# Copy the following to ~/.ssh/config beforehand:
#
# Host cvr-virtual
#	HostName 10.0.0.6
#	ProxyCommand ssh -W %h:%p office.atvpc.com

servers=(45.79.150.195 172.104.213.209 50.249.20.86 10.0.0.6)

for server in ${servers[@]}; do
	ssh-copy-id -i $HOME/.ssh/id_ed25519.pub $server
done


