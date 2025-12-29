# GitHub Secrets Configuration

Configure these secrets in GitHub:
**Settings → Secrets and variables → Actions → New repository secret**

## Required Secrets for Deployment

### SSH Configuration
```
SSH_HOST=xman4289.com
SSH_USER=admin
SSH_PORT=22
SSH_PRIVATE_KEY=[your-ssh-private-key]
```

### Server Configuration
```
DEPLOY_PATH=/home/admin/domains/xman4289.com/public_html
APP_URL=https://xman4289.com
```

## Optional Secrets

### Slack Notifications (optional)
```
SLACK_WEBHOOK_URL=[your-slack-webhook]
```

### Discord Notifications (optional)
```
DISCORD_WEBHOOK_URL=[your-discord-webhook]
```

## How to Generate SSH Key

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions@xmanstudio" -f ~/.ssh/github-actions

# Copy public key to server
ssh-copy-id -i ~/.ssh/github-actions.pub admin@xman4289.com

# Copy private key content for GitHub Secret
cat ~/.ssh/github-actions
# Copy everything including BEGIN and END lines
```

## Testing Secrets

After adding secrets, test with:
```
Actions → Deploy to Production → Run workflow
Select environment: staging (test first)
```
