<template>
    <LoadingComponent :props="loading"/>
    <section class="mb-10 sm:mb-20">
        <div class="container">
            <div class="flex items-center justify-between gap-5 mb-6 max-md:mb-8">
                <div class="flex flex-wrap items-end gap-3 max-md:flex-col max-md:items-start max-md:gap-1.5">
                    <h3 class="text-3xl font-bold capitalize max-sm:text-lg">
                        {{ $t('label.daily_deals') }}
                    </h3>
                    <span class="text-xl font-medium capitalize max-sm:text-sm">
                        ({{ products.length }} {{ products.length > 1 ? $t('label.products_found') : $t('label.product_found') }})
                    </span>
                </div>
            </div>

            <div class="w-full max-md:p-0">
                <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6 mb-12">
                    <LoadingContentComponent :props="loadingContent"/>
                    <ProductListComponent v-if="products.length > 0" :products="products"/>
                </div>
                <PaginationComponent @pagination-change-page="dailyDealsProducts" :data="pagination" :limit="1"
                                     :keep-length="false"/>
            </div>
        </div>
    </section>
</template>

<script>

import ProductListComponent from "../components/ProductListComponent.vue";
import LoadingContentComponent from "../components/LoadingContentComponent.vue";
import LoadingComponent from "../components/LoadingComponent.vue";
import PaginationComponent from "../components/PaginationComponent.vue";

export default {
    name: "DailyDealsProductComponent",
    components: {
        PaginationComponent,
        LoadingComponent,
        LoadingContentComponent,
        ProductListComponent
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            loadingContent: {
                isActive: false
            },
        }
    },
    computed: {
        pagination: function () {
            return this.$store.getters["frontendProduct/dailyDealsProductPagination"];
        },
        products: function () {
            return this.$store.getters["frontendProduct/dailyDealsProducts"];
        }
    },
    mounted() {
        this.dailyDealsProducts();
    },
    methods: {
        dailyDealsProducts: function (page = 1) {
            this.loadingContent.isActive = true;
            this.$store.dispatch("frontendProduct/dailyDealsProducts", {
                paginate: 1,
                page: page,
                per_page: 30,
                order_column: "name",
                order_type: "asc",
            }).then((res) => {
                this.loadingContent.isActive = false;
            }).catch((err) => {
                this.loadingContent.isActive = false;
            });
        },
    }
}
</script>
