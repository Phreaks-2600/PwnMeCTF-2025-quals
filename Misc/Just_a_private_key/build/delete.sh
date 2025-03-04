#!/bin/bash

aws s3 rm s3://pwnme-private-assets/flag.txt
terraform init
terraform destroy
