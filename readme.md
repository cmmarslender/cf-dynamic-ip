Used to use ddclient for this, but it stopped working for whatever reason. Made this to use in the meantime.

### Usage

Run the docker container with the following environment variables, and it should just work.
* `ZONE` - The CloudFlare zone you are managing. If your record is a subdomain, this should be the MAIN domain, not the subdomain. `example.com`
* `RECORD` - The specific A record you want to assign the IP address to. `dynamic.example.com`
* `CF_EMAIL` - Your CloudFlare email address (required to use the API)
* `CF_API_KEY` - Your CloudFlare API Key (required to use the API)
* `IP_SERVICE` - A simple web service that echos back your ip address (such as `cmmarslender/ip-echo`). Your IP address MUST be the ONLY content this service returns in the body.
* `INTERVAL` - How often to check the IP, in seconds. Defaults to 300 (5 minutes) 

`docker run -it -e ZONE=example.com -e RECORD=dynamic.example.com -e CF_EMAIL=me@example.com -e CF_API_KEY=abc123 -e IP_SERVICE=ipecho.example.com cmmarslender/cf-dynamic-ip`
