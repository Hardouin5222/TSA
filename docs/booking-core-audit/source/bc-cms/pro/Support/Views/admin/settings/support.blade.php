<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title">{{__("Support Options")}}</h3>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-title"><strong>{{__("Ticket Options")}}</strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="">{{__("Ticket Assign To")}}</label>
                    <div>
                        @foreach(\Modules\User\Models\Role::all() as $role)
                            <label>
                                <input
                                        type="checkbox"
                                        name="support_ticket_assign_role[]"
                                        value="{{$role->id}}" {{in_array($role->id,setting_item_array('support_ticket_assign_role')) ? 'checked' : ''}}>{{ucfirst($role->name)}}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="form-group">
                    <label class="">{{__("Supporter View Type")}}</label>
                    <select class="form-control" name="support_ticket_view_type">
                        <option value="">{{__("Per user [Default]")}}</option>
                        <option
                                @if(setting_item('support_ticket_view_type') == 'all') selected @endif value="all"
                        >{{__("Supporter see all")}}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
