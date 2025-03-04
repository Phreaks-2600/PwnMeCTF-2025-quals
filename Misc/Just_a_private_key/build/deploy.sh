#!/bin/bash

terraform init
terraform apply
aws s3 cp flag.txt s3://pwnme-private-assets/flag.txt 
