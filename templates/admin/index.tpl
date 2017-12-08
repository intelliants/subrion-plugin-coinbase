{if $token}
    <div class="widget widget-default">
        <div class="widget-content">
            <table class="table">
                <tr>
                    <td>Access token:</td>
                    <td>{$token.access_token}</td>
                </tr>
                <tr>
                    <td>Token type:</td>
                    <td>{$token.token_type}</td>
                </tr>
                <tr>
                    <td>Refresh token:</td>
                    <td>{$token.refresh_token}</td>
                </tr>
                <tr>
                    <td>Scope:</td>
                    <td>{$token.scope}</td>
                </tr>
            </table>
        </div>
    </div>
    <p class="box-simple" style="height: auto;">Token expires in <code>{$expires}</code> minutes.</p>
{else}
    <a class="btn btn-lg btn-primary" href="{$authorize_url}">{lang key='authorize_in_coinbase'}</a>
{/if}