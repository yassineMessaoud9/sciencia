import CouponComponent from "../../components/admin/coupons/CouponComponent.vue";
import CouponListComponent from "../../components/admin/coupons/CouponListComponent.vue";
import CouponShowComponent from "../../components/admin/coupons/CouponShowComponent.vue";

export default [
    {
        path: "/admin/coupons",
        component: CouponComponent,
        name: "admin.coupons",
        redirect: { name: "admin.coupons.list" },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "coupons",
            breadcrumb: "coupons",
        },
        children: [
            {
                path: "",
                component: CouponListComponent,
                name: "admin.coupons.list",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "coupons",
                    breadcrumb: "",
                },
            },
            {
                path: "show/:id",
                component: CouponShowComponent,
                name: "admin.coupon.show",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "coupons",
                    breadcrumb: "view",
                },
            },
        ],
    },
];
