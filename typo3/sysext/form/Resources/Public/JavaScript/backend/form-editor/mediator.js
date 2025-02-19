/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
import d from"jquery";import*as b from"@typo3/form/backend/form-editor/helper.js";let m=null,g=null;function r(){return m}function e(){return g}function w(){return r().getUtility()}function v(i,t,o){return r().assert(i,t,o)}function c(i){return w().isUndefinedOrNull(i)?b.setConfiguration(e().getConfiguration()):b.setConfiguration(i)}function u(){return r().getCurrentlySelectedFormElement()}function n(){return r().getPublisherSubscriber()}function l(){return r().getRootFormElement()}function f(){window.onbeforeunload=function(i){if(r().getUnsavedContent())return i=i||window.event,i&&(i.returnValue=r().getFormElementDefinition(l(),"modalCloseDialogMessage")),r().getFormElementDefinition(l(),"modalCloseDialogTitle")},n().subscribe("view/ready",()=>{e().onViewReadyBatch()}),n().subscribe("core/applicationState/add",(i,[t,o,s])=>{e().disableButton(d(c().getDomElementDataIdentifierSelector("buttonHeaderRedo"))),s>1&&o<=s?e().enableButton(d(c().getDomElementDataIdentifierSelector("buttonHeaderUndo"))):e().disableButton(d(c().getDomElementDataIdentifierSelector("buttonHeaderUndo")))}),n().subscribe("core/ajax/saveFormDefinition/success",(i,[t])=>{r().setUnsavedContent(!1),e().setPreviewMode(!1),e().showSaveSuccessMessage(),e().showSaveButtonSaveIcon(),r().setFormDefinition(t.formDefinition),e().addStructureRootElementSelection(),r().setCurrentlySelectedFormElement(l()),e().setStructureRootElementTitle(),e().setStageHeadline(),e().renderAbstractStageArea(),e().renewStructure(),e().renderPagination(),e().renderInspectorEditors()}),n().subscribe("core/ajax/saveFormDefinition/error",(i,[t])=>{e().showSaveButtonSaveIcon(),e().showSaveErrorMessage({message:t.message})}),n().subscribe("core/ajax/renderFormDefinitionPage/success",(i,[t,o])=>{e().renderPreviewStageArea(t)}),n().subscribe("core/ajax/error",(i,[t,o,s])=>{t.status!==0&&(e().showErrorFlashMessage(o,s),e().renderPreviewStageArea(t.responseText))}),n().subscribe("view/header/button/save/clicked",()=>{r().validationResultsHasErrors(r().validateFormElementRecursive(l(),!0))?e().showValidationErrorsModal():(e().showSaveButtonSpinnerIcon(),r().saveFormDefinition())}),n().subscribe("view/header/formSettings/clicked",()=>{e().setPreviewMode(!1),e().addStructureRootElementSelection(),r().setCurrentlySelectedFormElement(l()),e().renderAbstractStageArea(),e().renewStructure(),e().renderPagination(),e().showInspectorSidebar(),e().renderInspectorEditors()}),n().subscribe("view/header/button/newPage/clicked",(i,[t])=>{r().isRootFormElementSelected()&&e().selectPageBatch(0),e().showInsertPagesModal(t)}),n().subscribe("view/header/button/close/clicked",()=>{e().showCloseConfirmationModal()}),n().subscribe("view/undoButton/clicked",()=>{e().disableButton(d(c().getDomElementDataIdentifierSelector("buttonHeaderUndo"))),e().disableButton(d(c().getDomElementDataIdentifierSelector("buttonHeaderRedo"))),r().undoApplicationState(),e().getPreviewMode()?r().renderCurrentFormPage():e().renderAbstractStageArea(),r().setUnsavedContent(!0),e().renewStructure(),e().renderPagination(),e().renderInspectorEditors()}),n().subscribe("view/redoButton/clicked",()=>{e().disableButton(d(c().getDomElementDataIdentifierSelector("buttonHeaderUndo"))),e().disableButton(d(c().getDomElementDataIdentifierSelector("buttonHeaderRedo"))),r().redoApplicationState(),e().getPreviewMode()?r().renderCurrentFormPage():e().renderAbstractStageArea(),r().setUnsavedContent(!0),e().renewStructure(),e().renderPagination(),e().renderInspectorEditors()}),n().subscribe("view/stage/element/clicked",(i,[t])=>{u().get("__identifierPath")!==t&&(r().setCurrentlySelectedFormElement(t),e().renewStructure(),e().refreshSelectedElementItemsBatch(),e().addAbstractViewValidationResults(),e().renderInspectorEditors())}),n().subscribe("view/stage/abstract/elementToolbar/button/newElement/clicked",(i,[t,o])=>{r().isRootFormElementSelected()&&e().selectPageBatch(0),e().showInsertElementsModal(t,o)}),n().subscribe("view/stage/abstract/button/newElement/clicked",(i,[t,o])=>{r().isRootFormElementSelected()&&e().selectPageBatch(0),e().showInsertElementsModal(t,o||void 0)}),n().subscribe("view/stage/abstract/dnd/start",(i,[t,o])=>{e().onAbstractViewDndStartBatch(t,o)}),n().subscribe("view/stage/abstract/dnd/stop",(i,[t])=>{r().setCurrentlySelectedFormElement(t),e().renewStructure(),e().setPreviewMode(!1),e().renderAbstractStageArea(!1,!1),e().refreshSelectedElementItemsBatch(),e().addAbstractViewValidationResults(),e().renderInspectorEditors()}),n().subscribe("view/stage/abstract/dnd/change",(i,[t,o,s])=>{e().onAbstractViewDndChangeBatch(t,o,s)}),n().subscribe("view/stage/abstract/dnd/update",(i,[t,o,s,a])=>{e().onAbstractViewDndUpdateBatch(t,o,s,a)}),n().subscribe("view/viewModeButton/abstract/clicked",()=>{e().getPreviewMode()&&(e().setPreviewMode(!1),e().renderAbstractStageArea())}),n().subscribe("view/viewModeButton/preview/clicked",()=>{e().getPreviewMode()||(e().setPreviewMode(!0),r().renderCurrentFormPage())}),n().subscribe("view/paginationPrevious/clicked",()=>{e().selectPageBatch(r().getCurrentlySelectedPageIndex()-1),e().getPreviewMode()?r().renderCurrentFormPage():e().renderAbstractStageArea()}),n().subscribe("view/paginationNext/clicked",()=>{e().selectPageBatch(r().getCurrentlySelectedPageIndex()+1),e().getPreviewMode()?r().renderCurrentFormPage():e().renderAbstractStageArea()}),n().subscribe("view/stage/abstract/render/postProcess",()=>{e().renderUndoRedo()}),n().subscribe("view/stage/preview/render/postProcess",()=>{e().renderUndoRedo()}),n().subscribe("view/tree/node/clicked",(i,[t])=>{let o;u().get("__identifierPath")!==t&&(o=r().getCurrentlySelectedPageIndex(),r().setCurrentlySelectedFormElement(t),e().setPreviewMode(!1),o!==r().getCurrentlySelectedPageIndex()?e().renderAbstractStageArea():e().renderAbstractStageArea(!1),e().renderPagination(),e().renderInspectorEditors())}),n().subscribe("view/tree/node/changed",(i,[t,o])=>{const s=r().getFormElementByIdentifierPath(t);s.set("label",o),e().getStructure().setTreeNodeTitle(null,s),u().get("__identifierPath")===t&&e().renderInspectorEditors(t,!1)}),n().subscribe("view/structure/root/selected",()=>{r().isRootFormElementSelected()||(e().addStructureRootElementSelection(),r().setCurrentlySelectedFormElement(l()),e().setPreviewMode(!1),e().renderAbstractStageArea(),e().renewStructure(),e().renderPagination(),e().renderInspectorEditors())}),n().subscribe("view/structure/button/newPage/clicked",(i,[t])=>{r().isRootFormElementSelected()&&e().selectPageBatch(0),e().showInsertPagesModal(t)}),n().subscribe("view/tree/dnd/stop",(i,[t])=>{r().setCurrentlySelectedFormElement(t),e().renewStructure(),e().renderPagination(),e().setPreviewMode(!1),e().renderAbstractStageArea(),e().renderInspectorEditors()}),n().subscribe("view/tree/dnd/change",(i,[t,o,s])=>{e().onStructureDndChangeBatch(t,o,s)}),n().subscribe("view/tree/dnd/update",(i,[t,o,s,a])=>{e().onStructureDndUpdateBatch(t,o,s,a)}),n().subscribe("view/structure/renew/postProcess",()=>{e().addStructureValidationResults()}),n().subscribe("view/inspector/removeCollectionElement/perform",(i,[t,o,s])=>{e().removePropertyCollectionElement(t,o,s||void 0)}),n().subscribe("view/inspector/collectionElement/new/selected",(i,[t,o])=>{e().createAndAddPropertyCollectionElement(t,o)}),n().subscribe("view/inspector/collectionElement/existing/selected",(i,[t,o])=>{e().renderInspectorCollectionElementEditors(o,t)}),n().subscribe("view/inspector/collectionElements/dnd/update",(i,[t,o,s,a])=>{s?e().movePropertyCollectionElement(t,"before",s,a):o?e().movePropertyCollectionElement(t,"after",o,a):v(!1,"Next element or previous element need to be set.",1477407673)}),n().subscribe("core/formElement/somePropertyChanged",(i,[t,o,s,a])=>{t!=="renderables"&&(!r().isRootFormElementSelected()&&t==="label"?e().getStructure().setTreeNodeTitle():!r().getUtility().isUndefinedOrNull(a)&&l().get("__identifierPath")===a&&(e().setStructureRootElementTitle(),e().setStageHeadline()),e().getPreviewMode()?r().renderCurrentFormPage():e().renderAbstractStageArea(!1,!1),e().addStructureValidationResults()),r().setUnsavedContent(!0)}),n().subscribe("view/formElement/removed",(i,[t])=>{r().setCurrentlySelectedFormElement(t),e().renewStructure(),e().renderAbstractStageArea(),e().renderPagination(),e().renderInspectorEditors()}),n().subscribe("view/formElement/inserted",(i,[t])=>{r().setCurrentlySelectedFormElement(t),e().renewStructure(),e().renderAbstractStageArea(),e().renderPagination(),e().renderInspectorEditors()}),n().subscribe("view/collectionElement/new/added",()=>{e().renderInspectorEditors()}),n().subscribe("view/collectionElement/moved",()=>{e().renderInspectorEditors(void 0,!1)}),n().subscribe("view/collectionElement/removed",()=>{e().renderInspectorEditors(void 0,!1)}),n().subscribe("view/insertElements/perform/bottom",(i,[t])=>{const o=r().getLastTopLevelElementOnCurrentPage();o?!r().getFormElementDefinition(o,"_isTopLevelFormElement")&&r().getFormElementDefinition(o,"_isCompositeFormElement")?e().createAndAddFormElement(t,r().getCurrentlySelectedPage()):e().createAndAddFormElement(t,o):e().createAndAddFormElement(t,r().getCurrentlySelectedPage())}),n().subscribe("view/insertElements/perform/after",(i,[t])=>{let o;o=e().createAndAddFormElement(t,void 0,!0),o=e().moveFormElement(o,"after",r().getCurrentlySelectedFormElement()),n().publish("view/formElement/inserted",[o])}),n().subscribe("view/insertElements/perform/inside",(i,[t])=>{e().createAndAddFormElement(t)}),n().subscribe("view/insertPages/perform",(i,[t])=>{e().createAndAddFormElement(t)}),n().subscribe("view/modal/close/perform",()=>{r().setUnsavedContent(!1),e().closeEditor()}),n().subscribe("view/modal/removeFormElement/perform",(i,[t])=>{e().removeFormElement(t)}),n().subscribe("view/modal/removeCollectionElement/perform",(i,[t,o,s])=>{e().removePropertyCollectionElement(t,o,s)}),n().subscribe("view/modal/validationErrors/element/clicked",(i,[t])=>{let o;u().get("__identifierPath")!==t&&(o=r().getCurrentlySelectedPageIndex(),r().setCurrentlySelectedFormElement(t),e().getPreviewMode()&&e().setPreviewMode(!1),o!==r().getCurrentlySelectedPageIndex()?e().renderAbstractStageArea():e().renderAbstractStageArea(!1),e().renderPagination(),e().renderInspectorEditors())})}function p(i,t){m=i,g=t,b.bootstrap(m),f()}export{p as bootstrap};
