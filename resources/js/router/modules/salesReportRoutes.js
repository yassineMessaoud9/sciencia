import SalesReportComponent from "../../components/admin/salesReport/SalesReportComponent.vue";
import SalesReportListComponent from "../../components/admin/salesReport/SalesReportListComponent.vue";
export default [
    {
        path: "/admin/sales-report",
        component: SalesReportComponent,
        name: "admin.sales-report",
        redirect: { name: "admin.sales-report.list" },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "sales-report",
            breadcrumb: "sales_report",
        },
        children: [
            {
                path: "",
                component: SalesReportListComponent,
                name: "admin.sales-report.list",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "sales-report",
                    breadcrumb: "",
                },
            },
        ],
    },
];
