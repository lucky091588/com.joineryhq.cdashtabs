<div class="crm-block crm-form-block crm-admin-options-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout-compressed">
      <tr class="crm-admin-options-form-block-label">
        <td class="label">{$form.label.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_value' field='label' id=$id}{/if}</td>
        <td class="html-adjust">{$form.label.html}<br />
          <span class="description">{ts}The option label is displayed to users.{/ts}</span>
        </td>
      </tr>
      <tr class="crm-admin-options-form-block-is_cdash">
        <td class="label">{$form.is_cdash.label}</td>
        <td>{$form.is_cdash.html}</td>
      </tr>
      <tr class="crm-admin-options-form-block-is_show_pre_post">
        <td class="label">{$form.is_show_pre_post.label}</td>
        <td>{$form.is_show_pre_post.html}</td>
      </tr>
      <tr class="crm-admin-options-form-block-weight">
        <td class="label">{$form.weight.label}</td>
        <td>{$form.weight.html}</td>
      </tr>
    </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>