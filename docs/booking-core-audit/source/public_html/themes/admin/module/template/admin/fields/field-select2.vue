<template>
    <div style="width: 100%">
        <v-select @open="openSearch" :multiple="isMultiple" v-model="value" :reduce="op => op.id"  label="text" :options="options" @search="fetchOptions">
            <template slot="no-options">
                Type to select
            </template>
        </v-select>
    </div>
</template>
<script>
    import vSelect from 'vue-select'
    import abstractField from "@/libs/vue-form-generator/src/fields/abstractField";

    export default {
        mixins: [ abstractField ],
        data(){
            return {
                options:[],
                selectedText:'',
                valueOption:null
            }
        },
        computed:{
            isMultiple(){
                return this.schema.select2.multiple == "true"
            }
        },
        mounted(){
            if(this.value && !this.options.length){
                this.initDefaultValue();
            }
        },
        watch   : {
            value: function (value) {
                if(value && !this.options.length){  
                    this.initDefaultValue();
                }

            }
        },
        components:{
            vSelect
        },
        methods:{
            initDefaultValue:function(){
                const me = this;
                $.ajax({
                    url:this.schema.select2.ajax.url,
                    method:'get',
                    data:{
                        pre_selected:1,
                        selected:this.value
                    },
                    success:function(json){

                        if(!json.results && !json.items){
                            return;
                        }
                        if(json.items){
                            json.results = json.items;
                        }

                        if(!Array.isArray(json.results)){
                            json.results = [json.results];
                        }

                        const newOptions = json.results.map(item => {
                            return {
                                id: item.id,
                                text: item.text || item.name || item.title
                            }
                        })

                        console.log(newOptions)
                        me.options = newOptions;

                    },
                    error:function(e){

                    }

                })
            },
            fetchOptions:function (search, loading){
                const me = this;
                loading(true);
                $.ajax({
                    url:this.schema.select2.ajax.url,
                    method:'get',
                    data:{
                        q:search
                    },
                    success:function(json){
                        loading(false);
                        me.options = json.results;
                    },
                    error:function(e){
                        loading(false);
                    }

                })
            },
            openSearch:function (e){
                const me = this;
                $.ajax({
                    url:this.schema.select2.ajax.url,
                    method:'get',
                    success:function(json){
                        me.options = json.results;
                    },
                    error:function(e){
                    }

                })
            }
        }
    };
</script>
