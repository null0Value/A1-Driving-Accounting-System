@if(!(is_null($eAccountTitle->id)))
   <div class="row">
      <div class="input-field col s12 m12 l12">
         <input type="hidden" name = "account_title_id" value="{{$eAccountTitle->id}}">
         <input type="hidden" name="account_group_id" value="{{$eAccountTitle->group->id}}">
         <input name="parent_account_title_name" value="{{ count($errors) > 0? old('parent_account_title_name'):($eAccountTitle->account_title_name) }}" type="text" class="active" readonly>
         <label for="first-name">Parent Account Title</label>
      </div>
  </div>
@else
   <div class="row">
      <div class="input-field col s12 m12 l12">
         @if(count($accountGroupsList)==1)
            <input type="hidden" name = "account_group_id" value="{{$accountGroupsList->id}}">
            <input id="name" type="text" name="account_group_name" value="{{count($errors)>0?old('account_group_name'):$accountGroupsList->account_group_name}}" disabled>
            <label for="account_title_name" class="active">Account Title</label>
         @else
            <select name="account_group_id">
               @foreach($accountGroupsList as $key=>$value)
                  <option value="{{$key}}">{{$value}}</option>
               @endforeach
            </select>
            <label>Account Group</label>
         @endif
      </div>
   </div>
@endif

<div class="row">
	<div class="input-field col s12 m12 l12">
      <input id="name" type="text" name="account_title_name" value="{{count($errors)>0?old('account_title_name'):$accountTitle->account_title_name}}">
      <label for="account_title_name">Account Title</label>
   </div>
</div>

<div class="row">
   @if(count($accountGroupsList)==1  && ($accountGroupsList->account_group_name!='Revenues' && $accountGroupsList->account_group_name!='Expenses'))
      <div class="input-field col s12 m12 l12">
         <input id="opening_balance" type="number" step="0.01" min="0" name="opening_balance" value="{{count($errors)>0?old('opening_balance'):$accountTitle->opening_balance}}">
         <label for="opening_balance">Opening Balance</label>
      </div>
   @endif
   <div class="input-field col s12 m12 l12">
      <textarea class="materialize-textarea" name="description" id="desc" cols="30" rows="10">{{count($errors)>0?old('description'):$accountTitle->description}}</textarea>
      <label for="desc">Description</label>
   </div>
</div>


<div class="row right-align">
 <div class="col l12 m12 s12">
   <button class="btn cyan waves-effect waves-light right" type="submit" name="action">{{$submitButton}}
    <i class="mdi-content-send right"></i>
    </button>
 </div>
</div>
                                 